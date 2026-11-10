"""Ensure changed, missing, extra and private-overlay source cannot pass silently."""
import tempfile
import unittest
from pathlib import Path
from source_manifest import digest, verify


class VerificationTests(unittest.TestCase):
    def test_complete_boundary(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            (root / 'classes').mkdir()
            source = root / 'classes/example.php'
            source.write_text('<?php // shared')
            manifest = {'files': {'classes/example.php': digest(source.read_bytes())}}
            self.assertEqual([], verify(root, manifest))
            source.write_text('<?php // local edit')
            self.assertTrue(any('changed:' in x for x in verify(root, manifest)))
            source.unlink()
            self.assertTrue(any('missing/' in x for x in verify(root, manifest)))
            source.write_text('<?php // shared')
            extra = root / 'repair.php'
            extra.write_text('<?php // untracked')
            self.assertTrue(any('unexpected:' in x for x in verify(root, manifest)))
            extra.unlink()
            private = root / 'classes/private.php'
            private.write_text('<?php // private')
            self.assertTrue(verify(root, manifest))
            overlay = {'classes/private.php': digest(private.read_bytes())}
            self.assertEqual([], verify(root, manifest, overlay))
            private.write_text('<?php // unexpected private edit')
            self.assertTrue(verify(root, manifest, overlay))

    def test_reject_symlinks(self):
        with tempfile.TemporaryDirectory() as temp:
            root = Path(temp)
            (root / 'classes').symlink_to('/tmp', target_is_directory=True)
            self.assertTrue(verify(root, {'files': {}}))


if __name__ == '__main__':
    unittest.main()

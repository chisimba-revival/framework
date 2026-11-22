import json
from pathlib import Path
import tempfile
import unittest
from prune_releases import plan


class RetentionTests(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory(); self.addCleanup(self.temp.cleanup)
        self.site = Path(self.temp.name)
        for name in ['old', 'previous', 'current']:
            (self.site / 'releases' / name / 'ch').mkdir(parents=True)
        (self.site / 'app').mkdir()
        (self.site / 'app/current').symlink_to(self.site / 'releases/current/ch')
        (self.site / 'backups/latest').mkdir(parents=True)
        for name, previous in [('current', 'previous'), ('previous', 'old')]:
            path = self.site / name / 'test/deployed.json'; path.parent.mkdir(parents=True)
            path.write_text(json.dumps({'status': 'online-verified', 'release': str(self.site / 'releases' / name / 'ch'), 'previous': str(self.site / 'releases' / previous / 'ch'), 'backup': str(self.site / 'backups/latest')}))
        self.capture = self.site / 'backups/automated/ready/manifest.json'
        self.capture.parent.mkdir(parents=True)
        self.capture.write_text(json.dumps({'format': 1, 'code_path': str(self.site / 'releases/current/ch'), 'captured_at': 1000, 'files': dict.fromkeys(['database.sql','code.tar','content.tar','operations.tar','images.tar','containers.json','database-checksums.tsv','worker-schedules.json'], {})}))

    def test_keeps_current_and_previous_without_mutating(self):
        result = plan(self.site, 'test', [self.site / 'releases/current/ch'], now=1001)
        self.assertEqual(result['remove'], [str(self.site / 'releases/old')])
        self.assertEqual(len(result['keep']), 2)
        self.assertTrue((self.site / 'releases/old').exists())

    def test_protects_mounted_old_release(self):
        with self.assertRaisesRegex(RuntimeError, 'still referenced'):
            plan(self.site, 'test', [self.site / 'releases/old/ch/config'], now=1001)

    def test_stale_capture_blocks_cleanup(self):
        with self.assertRaisesRegex(RuntimeError, 'recent complete capture'):
            plan(self.site, 'test', [], now=200000)

    def test_missing_previous_blocks_cleanup(self):
        (self.site / 'previous/test/deployed.json').unlink()
        with self.assertRaisesRegex(RuntimeError, 'not verified'):
            plan(self.site, 'test', [], now=1001)

    def test_symlink_candidate_is_rejected(self):
        (self.site / 'releases/linked').symlink_to(self.site / 'backups')
        with self.assertRaisesRegex(RuntimeError, 'Unexpected entry'):
            plan(self.site, 'test', [], now=1001)

    def test_new_candidate_is_rejected(self):
        import os
        path = self.site / 'releases/new'; path.mkdir()
        os.utime(path, (2000000000, 2000000000))
        with self.assertRaisesRegex(RuntimeError, 'Newer candidate'):
            plan(self.site, 'test', [], now=1001)


if __name__ == '__main__':
    unittest.main()

#!/usr/bin/env python3
"""Exercise the real SMTP transport against a loopback-only fixture, never real email."""
import pathlib
import socket
import subprocess
import threading
import tempfile

root = pathlib.Path(__file__).resolve().parents[2]
for reject in (False, True):
    listener = socket.socket()
    listener.bind(('127.0.0.1', 0))
    listener.listen(1)
    listener.settimeout(15)
    port = listener.getsockname()[1]
    commands, messages, errors = [], [], []
    def capture():
        try:
            conn, _ = listener.accept()
            with conn, conn.makefile('rwb') as stream:
                stream.write(b'220 fixture ESMTP\r\n'); stream.flush()
                while line := stream.readline():
                    commands.append(line.decode().strip())
                    if line.upper().startswith(b'DATA'):
                        stream.write(b'354 data\r\n'); stream.flush()
                        message = b''
                        while (part := stream.readline()) != b'.\r\n':
                            if not part: raise RuntimeError('Incomplete DATA')
                            message += part
                        messages.append(message)
                        reply = b'250 captured\r\n'
                    elif line.upper().startswith(b'RCPT') and reject:
                        reply = b'550 fixture rejection\r\n'
                    elif line.upper().startswith(b'QUIT'):
                        stream.write(b'221 bye\r\n'); stream.flush(); break
                    else:
                        reply = b'250 fixture\r\n'
                    stream.write(reply); stream.flush()
        except Exception as exc:
            errors.append(exc)
        finally:
            listener.close()
    worker = threading.Thread(target=capture, daemon=True); worker.start()
    # Load the strict no-network preparation fixtures, then enable only the loopback capture.
    code = f'''<?php
require {str(root / 'tests/mail/mime_mail_test.php')!r};
$config->values['MAIL_SEND_METHOD'] = 'smtp';
$config->values['MAIL_SMTP_SERVER'] = '127.0.0.1';
$config->values['MAIL_SMTP_PORT'] = {port};
$config->values['MAIL_SMTP_REQUIRESAUTH'] = 'false';
$config->values['MAIL_SMTP_SECURITY'] = 'none';
$facade->setValue('bcc', 'private@example.invalid');
check($facade->send() === {'false' if reject else 'true'}, 'SMTP result');
'''
    with tempfile.NamedTemporaryFile(suffix='.php', mode='w') as fixture:
        fixture.write(code); fixture.flush()
        subprocess.run(['php', fixture.name], check=True, timeout=20)
    worker.join(15)
    assert not worker.is_alive() and not errors, errors
    assert any('RCPT TO:<private@example.invalid>' in c for c in commands), commands
    if reject:
        assert messages == []
    else:
        assert len(messages) == 1
        assert b'private@example.invalid' not in messages[0]
        assert b'Cc: copy@example.invalid' in messages[0]
print('Loopback SMTP acceptance, rejection and BCC envelope privacy passed')

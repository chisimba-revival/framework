#!/usr/bin/env python3
"""Create synthetic media only; requires ffmpeg. No downloaded/user media."""
import pathlib, subprocess, sys, wave
root=pathlib.Path(sys.argv[1]); root.mkdir(parents=True,exist_ok=True)
with wave.open(str(root/'tone.wav'),'wb') as out:
    out.setparams((1,2,8000,0,'NONE','not compressed'))
    out.writeframes(b'\0\0'*8000)
for suffix in ['mp3','flac','ogg']:
    subprocess.run(['ffmpeg','-v','error','-y','-i',str(root/'tone.wav'),'-metadata','title=Café & birds',str(root/('tone.'+suffix))],check=True)
for suffix in ['png','jpg','mp4']:
    subprocess.run(['ffmpeg','-v','error','-y','-f','lavfi','-i','color=c=blue:s=64x48:d=1','-frames:v','1' if suffix!='mp4' else '25',str(root/('image.'+suffix))],check=True)
(root/'empty.dat').write_bytes(b'')
(root/'bad.mp3').write_bytes(b'not a media file')

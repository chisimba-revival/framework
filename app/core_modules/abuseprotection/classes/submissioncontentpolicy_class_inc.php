<?php
/** Conservative shared review hints. These never delete content or decide identity. */
final class SubmissionContentPolicy
{
    public static function suspiciousName($name)
    {
        foreach (preg_split('/\s+/u', trim((string)$name)) as $part) {
            if (!preg_match('/^[A-Za-z]{14,50}$/D', $part)) continue;
            preg_match_all('/[a-z][A-Z]|[A-Z][a-z]/', $part, $transitions);
            preg_match_all('/[A-Z]/', $part, $upper);
            if (count($transitions[0]) >= 5 && count($upper[0]) >= 5) return true;
        }
        return false;
    }
    public static function reviewReason($name, $subject, $message)
    {
        if (self::suspiciousName($name)) return 'generated_name';
        $text = $subject."\n".$message;
        if (preg_match_all('~https?://|www\.|\[url(?:=|\])~i', $text) >= 3) return 'many_links';
        if (preg_match('/\b(seed phrase|recovery phrase|private key)\b/i', $text)
            && preg_match('/\b(send|share|provide|enter|verify)\b/i', $text)) return 'credential_request';
        return '';
    }
}

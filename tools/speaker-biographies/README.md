# Speaker biography presentation

Webinar speaker pages and speaker dialogs use the same biography renderer.
Separate profile images and links occupy a narrow right panel and stack after
the prose when space is limited. Images retain their proportions and never
enlarge beyond their intrinsic dimensions. Duplicate image/banner URLs are
shown once; no saved payload is rewritten. Webinar banner/card rendering is
unchanged.

The shared content-block image rule no longer forces rich-text images to 100%
width. Authored dimensions/alignment remain intact, with responsive shrinking.
Unformatted biography images float left with a 1.25rem gap; at phone widths
they stack with a 1rem bottom gap. Explicit styles/alignment remain authoritative.
The speaker editor places its existing profile-image controls in the sidebar.
Links continue saving independently through the existing Ajax editor.

Validation on 11 November 2026:

- PHP syntax and whitespace checks; webinar archive and editor contracts.
- Shared composition validation and skin ownership/cache-version contracts.
- Local browser: original 300 × 225 portrait stays 300 × 225 on desktop;
  separate 640px logo shrinks to the sidebar. Text wraps with 20px spacing.
- At 390px and 320px, no horizontal overflow; images shrink when necessary,
  profile panel stacks, and phone text remains below inline images.
- Biography Ajax save and independent profile-link save succeeded. The link
  appeared in the public sidebar. Help opened and Escape restored focus.
- Disposable local speaker and link removed after testing.

No production biography or image content is edited by this change.

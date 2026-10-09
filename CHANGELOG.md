# Changelog

## 1.0.1

- Supply the required Axolotl-PM 5.49.1 sound packet fields when playing the built-in toast sound.
- Report the actual resource path when a bundled resource cannot be read or added to the pack.
- Validate resource iterator entries before using them.
- Run PHPStan level max, source style checks, PHP syntax, and resource-pack validation in CI with pinned Axolotl-PM development dependencies.
- Advance the resource-pack version to 1.0.1 so clients can distinguish it from the earlier pack with the same UUID.

The public PHP API and resource-pack UUIDs are unchanged. To roll back, restore both the previous consumer PHAR and its matching CustomToast resource pack. A client may need to clear its cached server pack before testing an older build.

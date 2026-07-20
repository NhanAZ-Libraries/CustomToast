# Asset notice

The PHP source code in this repository is covered by the license in `LICENSE`.

The bundled toast artwork was created for this project by NhanAZ. It is not copied from the prototype server packs used during the early research phase.

The artwork is not covered by the source-code license. Contact the project owner before reusing or redistributing it outside CustomToast.

Project artwork:

- `resources/CustomToast/textures/ui/custom_toast/background_round_*.png`
- `resources/CustomToast/textures/ui/custom_toast/background_square_*.png`
- `resources/CustomToast/textures/ui/custom_toast/icon_*.png`

Toast audio is not bundled. The library plays Minecraft Bedrock's built-in `random.toast` sound event.

The neutral round and square source templates remain in the same directory. Run `php tools/generate-palette-assets.php` to create only missing Minecraft color variants. Existing PNGs are preserved so hand-edited artwork is never replaced accidentally.

`resources/CustomToast/pack_icon.png` uses the same project icon as CustomEmojis.

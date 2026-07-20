# CustomToast

CustomToast is a PocketMine-MP virion for displaying compact, animated toast notifications in the top-right corner of Minecraft: Bedrock Edition.

It gives plugin authors one small PHP API while handling the less friendly parts internally: resource-pack registration, UI routing, UTF-8-safe payloads, the network text packet, and the optional vanilla toast sound.

This repository is the canonical documentation and source for the library. The companion [CustomToastExample](https://github.com/NhanAZ-Plugins/CustomToastExample) repository is only a runnable demonstration of the API.

## Features

- Four built-in styles: info, success, warning, and error.
- Rounded or square corners, configurable globally or per toast.
- All 29 current Minecraft Bedrock formatting-code colors, plus automatic and neutral helpers.
- Optional icons with title-only, message-only, or title-and-message layouts.
- Vietnamese and other UTF-8 text is preserved safely.
- Minecraft's built-in vanilla toast sound can play with each notification.
- Multiple toasts can be queued by the Bedrock UI.
- Multiple plugins can safely share one server-side CustomToast runtime.
- The resource pack is compiled and registered automatically at server startup.
- No command syntax or plugin-specific behavior is forced on the library user.

## Requirements

- PocketMine-MP 5.x
- PHP 8.1 or newer
- PHP extensions `mbstring` and `zip`
- A Bedrock client that accepts the server resource pack

## Installation in a plugin build

CustomToast contains both PHP source and resource-pack files. Your build must inject both folders into the final plugin PHAR.

With Pockgin CLI, add this `pockgin.libs.yml` to the plugin that uses CustomToast:

```yaml
libs:
  - id: customtoast
    repo: NhanAZ-Libraries/CustomToast
    version: ^1.0.0
    target: src/NhanAZ/CustomToast
    src_path: src/NhanAZ/CustomToast

  - id: customtoast-resources
    repo: NhanAZ-Libraries/CustomToast
    version: ^1.0.0
    target: resources/CustomToast
    src_path: resources/CustomToast
```

Then build the host plugin:

```bash
node /path/to/pockgin-cli/bin/pockgin.js build .
```

The two entries are intentional. The first injects the PHP API; the second injects the client-side UI and images.

Keep the target namespace exactly as shown. Pockgin copies the library without namespace shading, so every consumer resolves the same `NhanAZ\CustomToast` classes and shares one runtime coordinator.

If you use a different build system, copy these two paths into the same locations in the host plugin before creating its PHAR:

```text
CustomToast/src/NhanAZ/CustomToast  ->  YourPlugin/src/NhanAZ/CustomToast
CustomToast/resources/CustomToast  ->  YourPlugin/resources/CustomToast
```

Do not install this repository directly in the PocketMine-MP `plugins` folder. It is a library, not a standalone plugin. Use [CustomToastExample](https://github.com/NhanAZ-Plugins/CustomToastExample) if you want a ready-to-run demonstration.

## Quick start

Create one library instance in your plugin's `onEnable()` method:

```php
use NhanAZ\CustomToast\CustomToast;
use NhanAZ\CustomToast\ToastColor;
use NhanAZ\CustomToast\ToastCornerStyle;
use NhanAZ\CustomToast\ToastType;
use pocketmine\plugin\PluginBase;

final class Main extends PluginBase{
    private CustomToast $customToast;

    protected function onEnable() : void{
        $this->customToast = CustomToast::create($this);
    }

    protected function onDisable() : void{
        $this->customToast->close();
    }
}
```

Send a message-only toast:

```php
$this->customToast->send(
    $player,
    ToastType::INFO,
    "You have a new friend request."
);
```

Choose any Minecraft Bedrock color independently of the toast type:

```php
$this->customToast->send(
    $player,
    ToastType::INFO,
    "Your party is ready.",
    "Party",
    null,
    ToastCornerStyle::ROUND,
    ToastColor::LIGHT_BLUE
);
```

Send a title and a message:

```php
$this->customToast->send(
    $player,
    ToastType::WARNING,
    "The server will restart in 5 minutes.",
    "Warning",
    null,
    ToastCornerStyle::SQUARE
);
```

Send a compact text-only toast without an icon:

```php
$this->customToast->send(
    player: $player,
    type: ToastType::INFO,
    message: "A plain notification without an icon.",
    showIcon: false
);
```

A title may also be used without a message or icon:

```php
$this->customToast->send(
    player: $player,
    type: ToastType::INFO,
    message: "",
    title: "Maintenance complete",
    showIcon: false
);
```

Messages may contain any number of explicit line breaks. The toast background grows vertically to fit the rendered lines:

```php
$this->customToast->send(
    $player,
    ToastType::SUCCESS,
    "First reward\nSecond reward\n\nCome back tomorrow.",
    "Daily rewards"
);
```

Send a toast to everyone online:

```php
$count = $this->customToast->broadcast(
    ToastType::SUCCESS,
    "The event is now open!",
    "Event"
);
```

## API

### Creating the service

```php
CustomToast::create(
    PluginBase $plugin,
    bool $forceResourcePack = true,
    int $maxMessageBytes = 256,
    bool $playSound = true,
    ToastCornerStyle $cornerStyle = ToastCornerStyle::ROUND,
    ToastColor $color = ToastColor::AUTO,
    bool $showIcon = true
): CustomToast
```

- `plugin` is the host plugin containing the injected library and resources.
- `forceResourcePack` disconnects clients that refuse the pack when set to `true`.
- `maxMessageBytes` limits the message field without cutting a UTF-8 character in half.
- `playSound` is the default sound behavior for every toast.
- `cornerStyle` is the default background shape for every toast.
- `color` is the default background color. `AUTO` chooses a color from the toast type.
- `showIcon` selects whether to show the type icon by default. Iconless toasts automatically use compact left padding.

Create the service once, during startup, before players join. Registering a resource pack after a player has joined will not make that client download it automatically.

### Sending one toast

```php
$customToast->send(
    Player $player,
    ToastType $type,
    string $message,
    ?string $title = null,
    ?bool $playSound = null,
    ?ToastCornerStyle $cornerStyle = null,
    ?ToastColor $color = null,
    ?bool $showIcon = null
): void
```

Pass `null` as the title for a message-only toast. Pass an empty message with a non-empty title for a title-only toast. The four optional final arguments override the default sound, corner, color, and icon settings for that one toast. Pass `false` as `showIcon` to remove the icon and its reserved space.

The title is limited to 96 UTF-8 bytes and remains a single line; newline and tab characters in it are converted to spaces. Message line breaks are preserved, including repeated line breaks that create an empty line. Tabs in the message are converted to spaces.

### Broadcasting

```php
$customToast->broadcast(
    ToastType $type,
    string $message,
    ?string $title = null,
    ?bool $playSound = null,
    ?ToastCornerStyle $cornerStyle = null,
    ?ToastColor $color = null,
    ?bool $showIcon = null
): int
```

The return value is the number of online players that received the toast.

### Closing

```php
$customToast->close();
```

Call `close()` from the host plugin's `onDisable()` method. It removes the generated pack from the runtime resource stack and deletes the temporary `.mcpack`. Calling it more than once is safe.

When several plugins use CustomToast, `close()` releases only that plugin's reference. The pack remains active until the final consumer closes.

## Using CustomToast from multiple plugins

Every plugin may inject the same `CustomToast` source and call `CustomToast::create()` independently:

```php
protected function onEnable() : void{
    $this->customToast = CustomToast::create($this);
}

protected function onDisable() : void{
    $this->customToast?->close();
}
```

The shared runtime applies these rules:

1. The first consumer compiles and registers the resource pack.
2. Later consumers reuse that registration instead of adding the same UUID again.
3. If any active consumer sets `forceResourcePack` to `true`, the pack remains required.
4. Closing one consumer does not interrupt the others.
5. The final `close()` restores the original required-pack setting, unregisters the pack, and removes the generated `.mcpack`.

All consumers should inject the same CustomToast release and the same presentation assets. The first plugin enabled by PocketMine-MP supplies the active pack file. If different plugins bundle different themes under the same pack UUID, startup order would decide which theme players receive.

## Toast types

```php
ToastType::INFO;
ToastType::SUCCESS;
ToastType::WARNING;
ToastType::ERROR;
```

For command parsers, `ToastType::fromName()` understands the full names and the short forms `i`, `s`, `w`, and `e`.

## Corner styles

```php
ToastCornerStyle::ROUND;
ToastCornerStyle::SQUARE;
```

Use the `cornerStyle` argument of `create()` to select a plugin default. Use the `cornerStyle` argument of `send()` or `broadcast()` to override it for one notification. `ToastCornerStyle::fromName()` accepts `round`, `rounded`, `square`, `sharp`, `r`, and `s`.

## Colors

`ToastColor::AUTO` selects `BLUE`, `GREEN`, `YELLOW`, or `RED` for info, success, warning, or error respectively. `ToastColor::NEUTRAL` resolves to `DARK_GRAY`. Every other case maps one-to-one to a current Minecraft Bedrock formatting code:

| Case | Code | Hex | Case | Code | Hex |
|---|---:|---:|---|---:|---:|
| `BLACK` | `§0` | `#000000` | `DARK_BLUE` | `§1` | `#0000AA` |
| `DARK_GREEN` | `§2` | `#00AA00` | `DARK_AQUA` | `§3` | `#00AAAA` |
| `DARK_RED` | `§4` | `#AA0000` | `DARK_PURPLE` | `§5` | `#AA00AA` |
| `GOLD` | `§6` | `#FFAA00` | `GRAY` | `§7` | `#AAAAAA` |
| `DARK_GRAY` | `§8` | `#555555` | `BLUE` | `§9` | `#5555FF` |
| `GREEN` | `§a` | `#55FF55` | `AQUA` | `§b` | `#55FFFF` |
| `RED` | `§c` | `#FF5555` | `LIGHT_PURPLE` | `§d` | `#FF55FF` |
| `YELLOW` | `§e` | `#FFFF55` | `WHITE` | `§f` | `#FFFFFF` |
| `MINECOIN_GOLD` | `§g` | `#DDD605` | `MATERIAL_QUARTZ` | `§h` | `#E3D4D1` |
| `MATERIAL_IRON` | `§i` | `#CECACA` | `MATERIAL_NETHERITE` | `§j` | `#443A3B` |
| `MATERIAL_REDSTONE` | `§m` | `#971607` | `MATERIAL_COPPER` | `§n` | `#B4684D` |
| `MATERIAL_GOLD` | `§p` | `#DEB12D` | `MATERIAL_EMERALD` | `§q` | `#47A036` |
| `MATERIAL_DIAMOND` | `§s` | `#2CBAA8` | `MATERIAL_LAPIS` | `§t` | `#21497B` |
| `MATERIAL_AMETHYST` | `§u` | `#9A5CC6` | `MATERIAL_RESIN` | `§v` | `#EB7114` |
| `LIGHT_BLUE` | `§w` | `#8BB3FF` |  |  |  |

`ToastColor::fromName()` accepts enum-style names such as `dark_blue`, short formatting codes such as `1`, and familiar aliases such as `resin`, `diamond`, `party_blue`, `neutral`, and `auto`. A leading `§` or `&` is accepted too.

## How it works

At startup, CustomToast collects the injected `resources/CustomToast` directory, creates `CustomToast.mcpack` in the host plugin's data directory, and places that pack at the top of PocketMine-MP's resource-pack stack.

When `send()` is called, the library sends a system `TextPacket` containing a private marker, a one-character type code that also carries the icon state, corner and color fields, followed by the display text. The custom HUD consumes marked messages, hides them from normal chat, builds the matching texture path, optionally selects the icon, and runs the enter/wait/exit animation. If sound is enabled, a `PlaySoundPacket` for Minecraft Bedrock's built-in `random.toast` event is sent immediately before the text packet. No custom audio file or sound definition is bundled.

The marker format is an implementation detail. Plugin code should always call the public API instead of assembling packets manually.

## Customizing the design

You may replace the files below while keeping their paths unchanged:

```text
resources/CustomToast/textures/ui/custom_toast/background_round_<official_name>.png
resources/CustomToast/textures/ui/custom_toast/background_square_<official_name>.png
resources/CustomToast/textures/ui/custom_toast/icon_info.png
resources/CustomToast/textures/ui/custom_toast/icon_success.png
resources/CustomToast/textures/ui/custom_toast/icon_warning.png
resources/CustomToast/textures/ui/custom_toast/icon_error.png
```

There are 58 active background files: 29 official color names for each corner style. For example, `background_round_green.png` is rounded green and `background_square_material_resin.png` is square Resin. All are 12x12 nine-slice textures with a four-pixel slice. `background_round.png` and `background_square.png` are neutral source templates for `tools/generate-palette-assets.php`; the generator creates missing variants without overwriting hand-edited files. During pack compilation, the readable names are mapped to compact internal aliases used by JSON UI. The notification sound comes from the Bedrock client through the built-in `random.toast` event, so visual themes do not need to ship or license an audio file.

See [ASSETS.md](ASSETS.md) before distributing the bundled presentation assets.

## Compatibility notes

- Bedrock JSON UI files modify global HUD and chat controls. Another resource pack that replaces the same controls can override CustomToast or be overridden by it.
- CustomToast places its pack at highest priority to make its bindings reliable.
- Multiple plugins are supported when they inject the unchanged `NhanAZ\CustomToast` namespace. Namespace-shaded or independently renamed copies cannot share the runtime coordinator and are unsupported.
- Keep every consumer on the same CustomToast release and resource-pack theme.
- A resource-pack-only change may be cached by the client. During design work, change the pack UUID or clear the client's cached server packs when necessary. Keep release versions at `1.0.0` unless the project owner explicitly requests a version change.

## Troubleshooting

### The toast appears in chat

The client did not load the CustomToast resource pack, or another pack replaced the chat/HUD bindings. Confirm that the player accepted the pack and test CustomToast at the top of the pack stack.

### `resources/CustomToast` is missing at runtime

Only the PHP half of the virion was injected. Add the second Pockgin mapping for `resources/CustomToast` and rebuild the host plugin.

### Vietnamese text looks broken

Keep source files and configuration files encoded as UTF-8. Do not convert the text through an ASCII-only command or database column. CustomToast truncates by UTF-8 byte boundaries and does not replace Vietnamese characters.

### A literal `\\n` is visible

The library API accepts title and message as separate arguments. Converting a command-line `\\n` separator is the responsibility of the host plugin. The example plugin demonstrates a safe parser. The pipe character `|` has no special meaning and remains part of the message.

### Text beginning with a number is missing

Use the current resource pack. Older builds allowed Bedrock JSON UI to interpret number-leading text as a numeric expression. The current binding forces the extracted payload back to a string, so both `1 reward` and `Reward 1` render normally.

## License

The PHP source is licensed under LGPL-3.0-or-later. Presentation assets are handled separately; read [ASSETS.md](ASSETS.md).

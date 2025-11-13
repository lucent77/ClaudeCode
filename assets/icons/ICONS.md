# PWA Icons Guide

This directory should contain the PWA app icons in various sizes.

## Required Icon Sizes

The following icon sizes are required for the PWA manifest:

- **72x72** - icon-72.png
- **96x96** - icon-96.png
- **128x128** - icon-128.png
- **144x144** - icon-144.png
- **152x152** - icon-152.png
- **192x192** - icon-192.png (required for Android)
- **384x384** - icon-384.png
- **512x512** - icon-512.png (required for splash screen)

## How to Generate Icons

### Option 1: Online Icon Generator

1. Visit: https://www.pwabuilder.com/imageGenerator
2. Upload a high-resolution logo (1024x1024 recommended)
3. Download the generated icon pack
4. Extract and place all icons in this directory

### Option 2: Using Favicon Generator

1. Visit: https://realfavicongenerator.net/
2. Upload your logo
3. Select "Generate for PWA"
4. Download and extract icons to this directory

### Option 3: Manual Creation

Use an image editor (Photoshop, GIMP, etc.) to create icons manually:

1. Start with a 1024x1024 source image
2. Resize to each required dimension
3. Save as PNG with transparency
4. Name files according to the list above

## Design Guidelines

### Visual Style

- **Simple and recognizable**: Icons should be clear at small sizes
- **Solid background**: Use a solid color background matching your brand
- **Centered logo**: Place the main logo in the center with padding
- **Square format**: All icons should be perfect squares
- **High contrast**: Ensure logo is visible against the background

### Recommended Colors

For this Price Tracker app, consider:

- **Primary color**: #2563EB (blue from theme)
- **Logo color**: White or contrasting color
- **Background**: Solid primary color or white

### Padding

- Maintain 10-15% padding around the logo
- For maskable icons, use 20% padding (safe zone)

## Example Design

```
┌─────────────────────┐
│                     │
│    ┌─────────┐      │
│    │         │      │
│    │  LOGO   │      │  <- 10-15% padding
│    │         │      │
│    └─────────┘      │
│                     │
└─────────────────────┘
```

## Testing Icons

After adding icons, test them:

1. **iOS**: Add to home screen in Safari
2. **Android**: Install PWA from Chrome
3. **Desktop**: Install from browser

## Maskable Icons (Optional)

For better Android 8.0+ support, create maskable versions:

1. Use 20% safe zone padding
2. Name with `-maskable` suffix (e.g., `icon-192-maskable.png`)
3. Update manifest.json with separate entries

## Placeholder Icon

If you don't have icons yet, you can use a simple colored square as a placeholder:

1. Create a solid blue (#2563EB) square
2. Add a white tag icon or "PT" text in the center
3. Export in all required sizes

## Resources

- [PWA Icon Best Practices](https://web.dev/add-manifest/)
- [Maskable Icons Guide](https://web.dev/maskable-icon/)
- [PWA Builder](https://www.pwabuilder.com/)
- [Favicon Generator](https://realfavicongenerator.net/)

---

**Note**: Remember to update `manifest.json` if you change icon filenames or add new sizes.

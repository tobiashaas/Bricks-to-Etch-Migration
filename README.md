# Bricks to Etch Migration Plugin

WordPress plugin to migrate Bricks Builder content to Etch (Gutenberg-based page builder) with full CSS conversion and metadata preservation.

## Features

✨ **Complete Element Conversion**
- Section → Etch Section
- Container → Etch Container
- Div/Block → Etch Flex-Div
- Text → Paragraph with metadata
- Heading → Heading with metadata
- Image → Image with nestedData
- Icon → HTML block with FontAwesome
- Button → Core button block

🎨 **Full CSS Conversion to Logical Properties**
- `margin-top` → `margin-block-start`
- `width` → `inline-size`
- `top/right/bottom/left` → `inset-*`
- Preserves negative values
- Maintains CSS variables

🔧 **Custom CSS Handling**
- Parses custom CSS from Bricks classes
- Merges with standard properties
- Preserves child selectors
- Converts `%root%` placeholder

📝 **Etch Metadata Structure**
- Complete `etchData` for all elements
- Style ID mapping and references
- Proper `nestedData` for images
- Block definitions for text/heading

## Installation

1. Clone this repository
2. Copy `bricks-etch-migration` folder to `/wp-content/plugins/`
3. Activate plugin in WordPress admin
4. Configure Etch API settings

## Usage

1. **Configure Settings**
   - Go to Bricks to Etch → Settings
   - Enter Etch site URL
   - Generate and save API key

2. **Run Migration**
   - Go to Bricks to Etch → Migrate
   - Select content to migrate
   - Click "Start Migration"
   - Monitor progress

3. **Verify Results**
   - Check migrated pages in Etch
   - Verify styles are applied
   - Test in Etch editor

## Documentation

See [ETCH_MIGRATION_GUIDE.md](ETCH_MIGRATION_GUIDE.md) for detailed technical documentation.

## Requirements

- WordPress 5.8+
- Bricks Builder
- Etch Plugin
- PHP 7.4+

## Migration Process

```
Bricks Classes → CSS Conversion → Etch Styles
     ↓                                 ↓
Bricks Elements → Block Conversion → Etch Content
```

## Key Achievements

✅ **1:1 Visual Match** - Migrated pages look identical to Bricks  
✅ **All Styles Applied** - Complete CSS conversion  
✅ **Proper Structure** - Correct element hierarchy  
✅ **Editable in Etch** - Full editor support  
✅ **Logical Properties** - Modern CSS standards  

## Known Limitations

- Media files need manual migration (coming soon)
- Dynamic data not yet supported
- Responsive styles in development
- Advanced elements (sliders, etc.) converted to divs

## Development

### Setup
```bash
# Clone repository
git clone https://github.com/tobiashaas/Bricks-to-Etch-Migration.git

# Install in WordPress
cp -r Bricks-to-Etch-Migration/bricks-etch-migration /path/to/wordpress/wp-content/plugins/

# Activate plugin
wp plugin activate bricks-etch-migration
```

### Testing
```bash
# Run migration test
wp eval-file test-migration.php

# Check styles
wp option get etch_styles

# Verify content
wp post list --post_type=page
```

## File Structure

```
bricks-etch-migration/
├── includes/
│   ├── css_converter.php         # CSS to Logical Properties
│   ├── gutenberg_generator.php   # Element to Block conversion
│   ├── api_client.php            # Etch API communication
│   ├── migration_manager.php     # Orchestration
│   └── ...
├── assets/
│   ├── css/
│   └── js/
└── bricks-etch-migration.php     # Main plugin file
```

## Contributing

Contributions welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

GPL v2 or later

## Credits

Developed by Tobias Haas

## Support

For issues and questions:
- GitHub Issues: https://github.com/tobiashaas/Bricks-to-Etch-Migration/issues
- Documentation: [ETCH_MIGRATION_GUIDE.md](ETCH_MIGRATION_GUIDE.md)

## Changelog

### v1.0.0 (2025-01-19)
- ✨ Complete Etch migration with full CSS conversion
- 🎨 All physical properties → Logical properties
- 📝 Complete etchData metadata structure
- 🖼️ Image classes via nestedData
- 📄 Text/Heading with style references
- 🔧 Custom CSS parsing and merging
- 🐛 Fixed position properties with "0" values
- 🐛 Fixed duplicate image classes
- 🎯 1:1 visual match with Bricks

## Roadmap

- [ ] Media file migration
- [ ] Dynamic data conversion
- [ ] Responsive styles
- [ ] Advanced elements (sliders, accordions)
- [ ] Batch migration
- [ ] Progress tracking
- [ ] Rollback functionality
- [ ] Migration preview

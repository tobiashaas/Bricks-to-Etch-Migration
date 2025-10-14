# WordPress Test Environment Setup

## Prerequisites

1. **WordPress Installation** (5.0+)
2. **PHP 7.4+**
3. **MySQL 5.7+**
4. **Required Plugins:**
   - Bricks Builder (for source site)
   - Etch PageBuilder (for target site)
   - Advanced Custom Fields (optional)
   - MetaBox (optional)
   - JetEngine (optional)

## Setup Instructions

### 1. Source Site Setup (Bricks Builder)

```bash
# Install WordPress
wp core download --path=/path/to/source-site
wp core config --dbname=source_db --dbuser=user --dbpass=pass
wp core install --url=http://localhost/source-site --title="Source Site" --admin_user=admin --admin_password=admin --admin_email=admin@example.com

# Install Bricks Builder
wp plugin install bricks --activate

# Install optional plugins
wp plugin install advanced-custom-fields --activate
wp plugin install meta-box --activate
wp plugin install jetengine --activate

# Install our migration plugin
cp -r bricks-etch-migration /path/to/source-site/wp-content/plugins/
wp plugin activate bricks-etch-migration
```

### 2. Target Site Setup (Etch PageBuilder)

```bash
# Install WordPress
wp core download --path=/path/to/target-site
wp core config --dbname=target_db --dbuser=user --dbpass=pass
wp core install --url=http://localhost/target-site --title="Target Site" --admin_user=admin --admin_password=admin --admin_email=admin@example.com

# Install Etch PageBuilder
wp plugin install etch --activate

# Install optional plugins
wp plugin install advanced-custom-fields --activate
wp plugin install meta-box --activate
wp plugin install jetengine --activate

# Install our migration plugin
cp -r bricks-etch-migration /path/to/target-site/wp-content/plugins/
wp plugin activate bricks-etch-migration
```

### 3. Test Data Setup

```bash
# Copy test data to source site
cp test-data/bricks-test-content.php /path/to/source-site/wp-content/plugins/bricks-etch-migration/

# Create test posts with Bricks content
wp post create --post_type=page --post_title="Test Page" --post_status=publish
wp post meta set [POST_ID] _bricks_template_type content
wp post meta set [POST_ID] _bricks_editor_mode bricks
wp post meta set [POST_ID] _bricks_page_content_2 '[SERIALIZED_BRICKS_CONTENT]'
```

## Testing Checklist

### Phase 1: Basic Functionality
- [ ] Plugin activation
- [ ] Admin menu appears
- [ ] Dashboard loads
- [ ] No PHP errors in logs

### Phase 2: Plugin Detection
- [ ] Bricks Builder detected
- [ ] Etch PageBuilder detected
- [ ] Custom field plugins detected
- [ ] Validation system works

### Phase 3: Content Parsing
- [ ] Bricks content parsed correctly
- [ ] Element types recognized
- [ ] Settings extracted
- [ ] Error handling works

### Phase 4: CSS Conversion
- [ ] Global classes converted
- [ ] Etch styles generated
- [ ] CSS validation works
- [ ] Hash IDs generated

### Phase 5: Dynamic Data
- [ ] Bricks tags converted
- [ ] ACF fields converted
- [ ] MetaBox fields converted
- [ ] Modifiers converted

### Phase 6: Gutenberg Generation
- [ ] Etch blocks generated
- [ ] Group blocks created
- [ ] Metadata included
- [ ] HTML structure correct

### Phase 7: API Communication
- [ ] API key generation
- [ ] Connection validation
- [ ] Data export/import
- [ ] Error handling

### Phase 8: Migration Flow
- [ ] 7-step process
- [ ] Progress tracking
- [ ] Resume capability
- [ ] Error recovery

## Test Scenarios

### Scenario 1: Simple Page Migration
1. Create page with Bricks content
2. Start migration
3. Verify content on target site
4. Check CSS classes
5. Verify dynamic data

### Scenario 2: Complex Page Migration
1. Create page with multiple sections
2. Add custom fields
3. Use dynamic data
4. Start migration
5. Verify all components

### Scenario 3: Custom Post Type Migration
1. Create custom post type
2. Add Bricks content
3. Start migration
4. Verify CPT registration
5. Check content migration

### Scenario 4: Custom Fields Migration
1. Create ACF field groups
2. Add MetaBox configurations
3. Start migration
4. Verify field groups
5. Check field values

### Scenario 5: Error Handling
1. Create invalid content
2. Start migration
3. Verify error logging
4. Check error recovery
5. Test resume functionality

## Troubleshooting

### Common Issues

1. **Plugin not activating**
   - Check PHP version (7.4+)
   - Check WordPress version (5.0+)
   - Check file permissions
   - Check PHP error logs

2. **Admin interface not loading**
   - Check JavaScript console
   - Check CSS loading
   - Check AJAX endpoints
   - Check nonce validation

3. **API communication failing**
   - Check API key generation
   - Check network connectivity
   - Check CORS settings
   - Check SSL certificates

4. **Content not migrating**
   - Check Bricks content structure
   - Check element parsing
   - Check Gutenberg generation
   - Check target site setup

5. **CSS not converting**
   - Check global classes
   - Check CSS validation
   - Check Etch styles structure
   - Check hash generation

### Debug Mode

Enable debug mode in WordPress:

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check logs in `/wp-content/debug.log`

### Performance Testing

1. **Small Site** (1-10 pages)
   - Expected time: 1-5 minutes
   - Memory usage: < 128MB
   - Database queries: < 1000

2. **Medium Site** (10-50 pages)
   - Expected time: 5-15 minutes
   - Memory usage: < 256MB
   - Database queries: < 5000

3. **Large Site** (50+ pages)
   - Expected time: 15+ minutes
   - Memory usage: < 512MB
   - Database queries: < 10000

## Success Criteria

### V0.1.0 Success Criteria
- [ ] Plugin activates without errors
- [ ] Admin interface loads correctly
- [ ] Basic migration flow works
- [ ] Content converts correctly
- [ ] CSS converts correctly
- [ ] Dynamic data converts correctly
- [ ] Error handling works
- [ ] Progress tracking works

### V1.0 Success Criteria
- [ ] All V0.1.0 criteria met
- [ ] Custom fields migration works
- [ ] Custom post types migration works
- [ ] Cross-plugin conversion works
- [ ] Performance optimized
- [ ] User documentation complete
- [ ] Error recovery robust
- [ ] Production ready

## Next Steps

1. **Complete V0.1.0 Testing**
2. **Fix any issues found**
3. **Optimize performance**
4. **Add missing features**
5. **Prepare for V1.0**
6. **Create user documentation**
7. **Prepare for production release**

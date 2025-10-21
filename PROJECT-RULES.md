# Project Rules - Bricks to Etch Migration

**Last Updated:** 2025-10-21 23:20

---

## 📋 General Rules

### 1. Documentation
- ✅ All documentation goes into `DOCUMENTATION.md`
- ✅ Always add timestamp when updating
- ✅ Keep documentation up-to-date with code changes

### 2. Changelog
- ✅ All changes MUST be documented in `CHANGELOG.md`
- ✅ Always add timestamp
- ✅ Format: `[Version] - YYYY-MM-DD (HH:MM)`
- ✅ Include: Features, Bug Fixes, Technical Changes

### 3. Todos
- ✅ All todos go into `TODOS.md`
- ✅ Always add timestamp
- ✅ Mark completed todos with ✅
- ✅ Remove completed todos after verification

### 4. File Creation
- ❌ **NEVER create new files without asking first**
- ✅ Always ask user before creating new documentation
- ✅ Update existing files instead of creating new ones
- ✅ Exception: Test scripts (see below)

### 5. Test Scripts
- ✅ All test scripts go into `/tests` folder
- ✅ Naming: `test-[feature].php` or `test-[feature].sh`
- ✅ Include description comment at top of file
- ✅ Clean up after testing

### 6. Converter Documentation
- ✅ All converter changes MUST be documented in `includes/converters/README.md`
- ✅ Always add timestamp when updating
- ✅ Remove old/outdated information - keep it clean!
- ✅ Document: Purpose, Features, Examples, Important Changes
- ✅ New converters: Add section with full documentation

---

## 📁 File Structure

```
bricks-etch-migration/
├── README.md                           # Main documentation
├── CHANGELOG.md                        # Version history (with timestamps)
├── DOCUMENTATION.md                    # Technical documentation (with timestamps)
├── TODOS.md                           # Todo list (with timestamps)
├── PROJECT-RULES.md                   # This file
├── cleanup-etch.sh                    # Cleanup tool
├── bricks-etch-migration/             # Plugin code
├── test-environment/                  # Docker setup
├── tests/                             # Test scripts
├── Masterplan/                        # Project planning
└── archive/                           # Old files
```

---

## 🔄 Workflow

### Making Changes

1. **Before coding:**
   - Check `TODOS.md` for current tasks
   - Update `TODOS.md` with new task (with timestamp)

2. **While coding:**
   - Make changes
   - Test changes
   - Document in code comments

3. **After coding:**
   - Update `CHANGELOG.md` (with timestamp)
   - Update `DOCUMENTATION.md` (with timestamp)
   - Mark todo as complete in `TODOS.md`
   - Test thoroughly

### Creating Test Scripts

1. Create in `/tests` folder
2. Name: `test-[feature].php` or `test-[feature].sh`
3. Add description comment
4. Document in `DOCUMENTATION.md` if needed

### Updating Documentation

1. Open `DOCUMENTATION.md`
2. Find relevant section
3. Update content
4. Add timestamp: `**Updated:** YYYY-MM-DD HH:MM`

---

## ✅ Examples

### Changelog Entry
```markdown
## [0.4.1] - 2025-10-21 (23:20)

### 🐛 Bug Fixes
- Fixed Custom CSS migration not merging with existing styles
- Updated `parse_custom_css_stylesheet()` to use existing style IDs
```

### Todo Entry
```markdown
- [ ] Fix Custom CSS migration - **Added:** 2025-10-21 23:15
- [✅] Update documentation - **Completed:** 2025-10-21 23:20
```

### Documentation Update
```markdown
## Custom CSS Migration

**Updated:** 2025-10-21 23:20

Custom CSS from Bricks Global Classes is now correctly merged...
```

---

## 🚫 Don'ts

- ❌ Don't create new markdown files without asking
- ❌ Don't create test scripts in root folder
- ❌ Don't update code without updating CHANGELOG
- ❌ Don't add todos without timestamp
- ❌ Don't leave completed todos in TODOS.md

---

## ✅ Do's

- ✅ Always ask before creating new files
- ✅ Always add timestamps to changes
- ✅ Keep documentation up-to-date
- ✅ Test changes thoroughly
- ✅ Clean up after testing
- ✅ Use existing files instead of creating new ones

---

**Created:** 2025-10-21 23:20  
**Version:** 1.0

# Publishing Guide - Laravel Addressable Package

This guide will walk you through the process of publishing version 2.0.0 of the Laravel Addressable package.

## 📋 Pre-Publishing Checklist

### ✅ Code Quality

- [x] All tests passing
- [x] Code formatted with Laravel Pint
- [x] No linting errors
- [x] Documentation updated

### ✅ Version Management

- [x] Version updated to 2.0.0 in composer.json
- [x] CHANGELOG.md created with comprehensive changes
- [x] README.md updated with modern documentation
- [x] LICENSE file added

### ✅ Package Structure

- [x] All source files in place
- [x] Configuration files ready
- [x] Tests organized and passing
- [x] GitHub Actions workflow configured

## 🚀 Publishing Steps

### Step 1: Final Verification

```bash
# Run all tests one final time
composer test

# Check code formatting
composer format:check

# Verify package structure
composer validate
```

### Step 2: Commit All Changes

```bash
# Add all files
git add .

# Commit with descriptive message
git commit -m "Release version 2.0.0 - Complete package modernization

- Added UUID support and soft deletes
- Implemented spatial operations and address validation
- Added comprehensive caching and geocoding features
- Modernized database schema and model architecture
- Enhanced testing with Pest framework
- Updated documentation and configuration
- Added security features and GDPR compliance"

# Push to main branch
git push origin main
```

### Step 3: Create Git Tag

```bash
# Create annotated tag
git tag -a v2.0.0 -m "Version 2.0.0 - Complete Package Modernization"

# Push tag to remote
git push origin v2.0.0
```

### Step 4: Publish to Packagist

#### Option A: Automatic Publishing (Recommended)

If you have GitHub integration set up with Packagist:

1. The package will be automatically published when you push the tag
2. Check Packagist dashboard to confirm the new version is available

#### Option B: Manual Publishing

```bash
# Create distribution archive
composer archive --format=zip --dir=dist

# Or publish directly (if you have Packagist credentials)
composer publish
```

### Step 5: Verify Publication

1. **Check Packagist**: Visit https://packagist.org/packages/awalhadi/addressable
2. **Verify Version**: Ensure 2.0.0 is listed as the latest version
3. **Test Installation**: Try installing in a fresh Laravel project
4. **Check Downloads**: Monitor download statistics



## 📞 Support

If you encounter any issues during publishing:

1. Check the [Laravel Package Development Guide](https://laravel.com/docs/packages)
2. Review [Packagist Documentation](https://packagist.org/about)
3. Ask for help in Laravel community channels
4. Create an issue in the repository

---

**Good luck with your package release! 🚀**

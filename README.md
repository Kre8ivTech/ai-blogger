# AI Blogger

**Version:** 1.3.0  
**Author:** Kre8ivTech, LLC  
**License:** GPL v2 or later

Automatically generates daily SEO-optimized blog posts about marketing concepts with AI-generated featured images and social media auto-posting capabilities.

## Features

### 🤖 AI Content Generation
- **Automated Blog Post Creation**: Generates SEO-optimized blog posts on marketing topics daily
- **GPT-4 Integration**: Uses OpenAI's GPT-4o for high-quality content generation
- **SEO Optimization**: Automatically generates meta descriptions, focus keywords, and tags
- **Business Context**: Customize content based on your business name, description, and target audience
- **Topic Management**: Tracks used topics to avoid repetition

### 🎨 AI Image Generation
- **OpenRouter Integration**: Uses OpenRouter API for flexible image generation
- **Multiple Model Support**: Choose from:
  - Flux (High Quality) - Recommended
  - Stable Diffusion XL
  - Gemini 2.0 Flash (Free tier available)
- **Professional Quality**: Generates high-quality, professional AI-generated featured images
- **No Stock Photo Aesthetics**: Avoids generic stock photo clichés

### 📱 Social Media Auto-Posting
- **Facebook Business Page**: Automatically post to your Facebook Business Page
- **LinkedIn Business Page**: Automatically post to your LinkedIn Company Page
- **Manual Posting**: Post to social media manually from:
  - Post History page
  - Individual post edit pages
- **Auto-Posting**: Automatically share posts when published (optional)
- **Connection Status**: Visual indicators show connection status for each platform

### ⚙️ Advanced Features
- **Scheduled Posting**: Set daily post generation time
- **Backfill Posts**: Generate posts for past dates
- **Post Status Control**: Choose draft, publish, or pending review
- **Category Management**: Random category assignment from selected categories
- **Activity Logging**: Track all generation activities
- **Word Count Control**: Customize target word count (500-3000 words)

## Installation

1. Upload the plugin files to `/wp-content/plugins/kre8iv-ai-blogger/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to **AI Blogger** → **Settings** to configure

## Configuration

### API Keys Required

#### OpenAI API Key
- Used for text content generation (GPT models)
- Get your key from [OpenAI Dashboard](https://platform.openai.com/api-keys)
- Required for: Blog post content, SEO metadata, topic generation

#### OpenRouter API Key
- Used for image generation
- Get your key from [OpenRouter Dashboard](https://openrouter.ai/keys)
- Required for: Featured image generation
- Supports multiple image generation models

### Social Media Setup

#### Facebook Business Page
1. Get your **Facebook Page ID**:
   - Use the "Get Page ID" button in settings for instructions
   - Or use [Graph API Explorer](https://developers.facebook.com/tools/explorer/)
2. Get your **Page Access Token**:
   - Use the "Get Access Token" button in settings for step-by-step instructions
   - Requires `pages_manage_posts` and `pages_read_engagement` permissions
3. Enable auto-posting (optional)

#### LinkedIn Business Page
1. Get your **LinkedIn Organization ID**:
   - Use the "Get Organization ID" button in settings for instructions
   - Numeric ID (not company name)
2. Get your **Access Token**:
   - Use the "Get Access Token" button in settings for instructions
   - Requires `w_member_social` and `w_organization_social` permissions
3. Enable auto-posting (optional)

## Usage

### Generating Posts

#### Manual Generation
1. Go to **AI Blogger** → **Dashboard**
2. Click **"Generate Post Now"**
3. Review the generated post
4. Edit if needed, then publish

#### Automatic Generation
1. Go to **AI Blogger** → **Settings**
2. Enable **"Auto-Posting"**
3. Set your **Daily Post Time**
4. Posts will generate automatically at the scheduled time

#### Backfill Posts
1. Go to **AI Blogger** → **Dashboard**
2. Use **"Single Backdate"** for one post or **"Bulk Backfill"** for multiple
3. Select date(s) and generate

### Social Media Posting

#### Auto-Posting
1. Configure Facebook and/or LinkedIn in **Settings**
2. Enable **"Enable Auto-Posting"** for desired platforms
3. When posts are published, they'll automatically post to social media

#### Manual Posting
- **From Post History**: Click Facebook or LinkedIn icons next to published posts
- **From Post Edit Page**: Use the "Social Media Posting" meta box in the sidebar

## Settings Overview

### API Configuration
- OpenAI API Key (for content)
- OpenRouter API Key (for images)
- GPT Model selection
- Image Model selection

### Business Context
- Business Name
- Business Description
- Target Audience

### Post Settings
- Default Post Status
- Post Categories
- Post Author
- Target Word Count
- Auto-Posting toggle

### Schedule Settings
- Daily Post Time
- Timezone support

### Social Media Settings
- Facebook Page ID & Access Token
- LinkedIn Organization ID & Access Token
- Auto-posting toggles for each platform

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- OpenAI API key (for content generation)
- OpenRouter API key (for image generation)
- Facebook Developer App (for Facebook posting)
- LinkedIn Developer App (for LinkedIn posting)

## Post Generation Process

1. **Topic Generation**: AI generates a unique marketing topic
2. **Content Creation**: GPT-4 writes SEO-optimized blog post
3. **SEO Metadata**: Generates meta description, keywords, and tags
4. **Image Generation**: Creates professional featured image via OpenRouter
5. **Post Creation**: Inserts post into WordPress
6. **Social Media**: Auto-posts to Facebook/LinkedIn if enabled

## Support

For issues, questions, or feature requests:
- Visit: https://www.kre8ivtech.com
- Check the Activity Log in **AI Blogger** → **Dashboard** for error details

## Changelog

### Version 1.3.0
- ✅ Added OpenRouter integration for image generation
- ✅ Added Facebook Business Page auto-posting
- ✅ Added LinkedIn Business Page auto-posting
- ✅ Added manual posting from Post History and Edit pages
- ✅ Added connection status indicators
- ✅ Added connection helper buttons with instructions
- ✅ Fixed image generation prompt (removed contradictory "real photography" requirements)
- ✅ Multiple image model support (Flux, Stable Diffusion XL, Gemini)

### Version 1.2.0
- Initial release with OpenAI DALL-E 3 integration

## License

GPL v2 or later

Copyright (c) 2024 Kre8ivTech, LLC
# DEF Skillbot Documentation

## Overview

**DEF Skillbot** is a comprehensive PHP-based Telegram chatbot system designed for educational and training purposes. Built on the **Tribe Framework**, it provides an interactive learning environment with multi-language support, progress tracking, assessments, and certificate generation. The system supports complex learning hierarchies including modules, levels, chapters, and assessments.

## Installation

### Docker Installation

DEF Skillbot can be easily deployed using it's official Docker template, which provides a complete containerized environment with all dependencies pre-configured.

#### Prerequisites

- **Docker** and **Docker Compose** installed
- **Git** for cloning repository
- **Minimum 2GB RAM** for containers
- **Port availability** for web services

#### Quick Start Installation

**Clone the Docker Template**:

```bash
sudo apt update

sudo apt install -y docker-ce docker-ce-cli containerd.io ; sudo systemctl start docker ; sudo systemctl enable docker ; sudo usermod -aG docker chatbot ; newgrp docker ; sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose ; sudo chmod +x /usr/local/bin/docker-compose ; sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose ; docker --version ; docker-compose --version ; sudo apt install -y git ; sudo git clone https://github.com/azeemkhandef/skillbot.def-dev.in.git ; sudo chown -R chatbot:chatbot skillbot.def-dev.in ; cd skillbot.def-dev.in ; chmod 755 uploads logs ; chmod +x scripts/docker-entrypoint.sh ; docker-compose build;

docker-compose up -d ;
docker-compose ps ;
curl http://localhost:8080
```

#### Useful Docker commands for management:

- View running containers: `docker ps`
- View all containers: `docker ps -a`
- Stop container: `docker stop skillbot-container`
- Start stopped container: `docker start skillbot-container`
- Remove container: `docker rm skillbot-container`
- View logs: `docker logs skillbot-container`

Your application will be accessible at `http://localhost:4040` once the container is running. The Dockerfile sets up a complete LEMP stack (Linux, Nginx, MySQL/MariaDB, PHP) with various tools and phpMyAdmin.

#### Post-Installation Setup

After successful Docker deployment:

1. **Access the Application**:

    - Skillbot: `http://localhost:4040` (or your configured port)

2. **Configure Telegram Webhook**:
   After deployment, manually activate the Telegram webhook by accessing:

```
https://your-domain.com/tool/botman-webhook.php
```

3. **Access Container Shell**:

```bash
docker compose exec tribe bash
```

4. **Database Backup**:

```bash
set -a && source .env && set +a && \
docker compose exec db mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > backup.sql
```

### Telegram Bot Activation

After successful deployment, the Telegram webhook must be manually activated by the development team:

1. **Access Webhook Tool**:
   Navigate to `https://your-domain.com/tool/botman-webhook.php` in your browser

2. **Webhook Configuration**:
   This tool will automatically configure the Telegram webhook to point to your `single-chatbot.php` endpoint

3. **Verification**:
   Test the bot by sending a message to your Telegram bot to ensure the webhook is properly configured

**Note**: This is a manual step that must be performed by the coding team each time a new bot is deployed or the webhook URL changes.

## System Architecture

### Core Components

1. **single-chatbot.php** - Main webhook handler for Telegram messages
2. **functions.php** - Core functionality and business logic

### Tribe Framework Integration

DEF Skillbot leverages the following classes:

- **Core Class** - Primary data management and object handling
- **MySQL Class** - Secure database operations with prepared statements
- **Config Class** - Content type definitions and configuration management

## File Breakdown

### 1. single-chatbot.php (Main Webhook Handler)

This is the primary entry point for processing Telegram webhook messages, built on PHP 8.0+.

#### Key Features:

- **Telegram Integration**: Receives and processes incoming Telegram messages
- **User Session Management**: Tracks user progress across conversations using Core class
- **Multi-user Support**: Handles multiple users on the same device with isolated data
- **Language Support**: Manages user language preferences with persistent storage
- **Progress Tracking**: Monitors completion status of modules and assessments

#### Core Workflow:

```
Incoming Telegram Message → User Identification → Session Management → Message Processing → Response Generation → Telegram Reply
```

#### Framework Integration:

```php
use Tribe\Core\Core as Core;
use Tribe\Core\MySQL as MySQL;

$core = new Core;
$sql = new MySQL;
```

#### Special Commands:

- `chatbot_reset` - Deletes all user data and restarts
- `chatbot_uid` - Returns user ID and response ID for debugging
- Home emoji (🏠) - Returns to main menu

#### Multi-user Functionality:

- Users can switch between multiple profiles on the same device
- Each user maintains separate progress and responses
- Supports adding new users dynamically
- User data isolation ensured through privacy controls

### 2. functions.php (Core Business Logic)

This file contains the `Functions` class with all the core chatbot functionality.

#### Key Methods:

##### Message Processing

- **`get_message_array()`** - Generates appropriate message and response options based on current state
- **`send_message()`** - Sends messages to Telegram using BotMan framework
- **`send_multi_message_return_last_one()`** - Handles sending multiple sequential messages

##### Data Handling

- **`derephrase()`** - Parses complex string formats (e.g., "option1##option2##option3")
- **`csv_to_array()`** - Converts CSV files to arrays for location/option data
- **`array_to_csv()`** - Exports data to CSV format

##### Content Management

- **`get_youtube_id()`** - Extracts YouTube video IDs from URLs
- **`get_answer_sheet()`** - Generates answer sheets for assessments
- **`join_images()`** - Combines multiple images using ImageMagick
- **`get_form_map()`** - Retrieves form mappings
- **`get_registration_form()`** - Fetches registration forms through Core class

#### Content Type Handlers:

##### Chatbot (Main Menu)

- Displays intro message using content system
- Shows available modules with completion status tracked via Core class
- Provides access to certificates, language settings, and reset options
- Manages multi-user switching functionality

##### Module

- Contains multiple levels managed through hierarchical content structure
- Tracks completion status using Core class attributes
- Manages pre-assessments with automated progression logic

##### Level

- Contains chapters or direct assessments
- Tracks chapter completion using persistent storage
- Handles post-assessments with score calculation

##### Chapter

- Contains sequential messages/content delivered progressively
- Navigates through content step-by-step
- Marks completion when finished using Core class methods

##### Form (Assessment)

- Handles questions and responses with validation
- Supports multiple question types:
    - Multiple choice with single/multiple selection
    - Text input with format validation
    - Mobile number validation with duplicate prevention
    - Location-based dropdowns (state/district/village) from CSV data
- Calculates scores with real-time tracking
- Prevents duplicate mobile numbers (configurable)
- Implements time limits for completion with fraud prevention

## Data Integration

### Data Structure

#### User Response Object (Stored via Core Class)

Each user interaction creates a response object containing:

```php
$obj = [
    'title' => $chatbot_slug.' '.$telegram_user_id,
    'type' => 'response',
    'content_privacy' => 'private',
    'chatbot' => $chatbot_slug,
    'telegram_user_id' => $telegram_user_id,
    'slug' => $core->slugify($chatbot_slug.' '.$telegram_user_id)
];
$response_id = $core->pushObject($obj);
```

#### Core Class Methods Used:

- **`$core->getObject($id)`** - Retrieve content objects
- **`$core->pushObject($array)`** - Create/update objects
- **`$core->pushAttribute($id, $key, $value)`** - Update specific attributes
- **`$core->getAttribute($id, $key)`** - Retrieve specific attributes
- **`$core->getIDs($search_array, $limit, $sort_field, $sort_order)`** - Advanced search

### Content Hierarchy

```
Chatbot (Content Type)
├── Module 1 (Content Object)
│   ├── Pre-assessment Form (Assessment Object)
│   ├── Level 1 (Content Object)
│   │   ├── Chapter 1 (Content Object)
│   │   ├── Chapter 2 (Content Object)
│   │   └── Post-assessment Form (Assessment Object)
│   └── Level 2 (Content Object)
│       └── ...
└── Module 2 (Content Object)
    └── ...
```

## Key Features

### 1. Multi-language Support

- Dynamic language selection at start stored via Core class
- All content supports multiple languages
- Language-specific message routing with persistent preferences

### 2. Progress Tracking

- Module completion status tracked as object attributes
- Chapter progression with real-time updates
- Assessment scores stored and calculated automatically
- Time tracking for modules with fraud prevention

### 3. Assessment System

- Pre and post assessments managed through the system
- Multiple question types with validation
- Score calculation with real-time updates
- Answer sheet generation with detailed feedback
- Time limit enforcement to prevent cheating

### 4. User Management

- Session persistence through Core class methods
- Multi-user support on single device with data isolation
- User switching functionality with secure data separation
- Privacy controls through content_privacy system

### 5. Content Delivery

- Sequential content navigation with state management
- Rich media support (images, YouTube videos)
- Keyboard-based navigation with dynamic options
- Emoji-enhanced interface with customizable icons

### 6. Data Validation

- Mobile number format validation with regex patterns
- Duplicate prevention using Core class search methods
- Required field enforcement through content type definitions
- Time-based completion validation with fraud detection

## Configuration

### Emoji Configuration

- `emoji_done` (✅) - Completed items
- `emoji_next` (👉) - Next action
- `emoji_home` (🏠) - Home/main menu
- `emoji_youwerehere` (➡️) - Current position indicator

### Behavioral Settings

- `do_not_allow_duplicate_mobile_numbers` - Prevents duplicate mobile registrations
- `allow_multiuser` - Enables multi-user functionality
- `time_limit` - Minimum time required for module completion (seconds)

### Language Configuration

- `languages` - Available language options in derephrase format
- `pre_assessment_word` - Label for pre-assessments per language
- `post_assessment_word` - Label for post-assessments per language

## Performance Optimization

### Tribe Framework Advantages

- **JSON Storage**: Flexible schema evolution without migrations
- **Prepared Statements**: Optimized database queries
- **Object Caching**: Built-in caching for frequently accessed content
- **Bulk Operations**: Efficient batch processing for related objects

### Recommended Optimizations

1. **Database Indexing**: Index frequently searched fields
2. **Content Caching**: Cache static content and configuration
3. **Image Optimization**: Use appropriate image sizes for Telegram
4. **Background Processing**: Handle heavy operations asynchronously

## Monitoring and Maintenance

### Health Checks

- Database connectivity
- Telegram API response times
- File system permissions
- Memory usage monitoring

### Backup Strategy

- **Database Backups**: Regular MySQL dumps
- **File Backups**: User uploads and configuration files
- **Version Control**: Code changes and configuration updates

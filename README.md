# Joomla YouTube Inline Video Plugin

This is a system plugin for Joomla that replaces all instances of the YouTube element with an inline YouTube video. The plugin works by first loading the thumbnail of the video and then loading the actual video iframe after the thumbnail is clicked.

## Features

- Replaces YouTube elements with inline YouTube videos
- Loads video thumbnails before loading the actual video
- Enhances page load speed by loading the video only when the thumbnail is clicked

## Installation

1. Download the plugin zip file.
2. Go to the Joomla administration panel.
3. Navigate to Extensions -> Manage -> Install.
4. Upload the downloaded zip file.

## Usage

After installation, the plugin will automatically replace all YouTube elements with inline YouTube videos.

For example, if you have a YouTube element in your article like this:

```html
<youtube data-id="dQw4w9WgXcQ">
<youtube video="dQw4w9WgXcQ">
```
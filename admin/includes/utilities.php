<?php

/**
 * Format duration in seconds to a human-readable format (HH:MM:SS)
 * 
 * @param int $seconds Duration in seconds
 * @return string Formatted duration string
 */
function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;

    if ($hours > 0) {
        return sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
    } else {
        return sprintf("%02d:%02d", $minutes, $seconds);
    }
} 
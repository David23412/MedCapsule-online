# Design Document

## Overview

This design document outlines the solution for fixing the critical UI issue with custom course categorization pills not displaying properly on the topics cards. The issue involves debug information showing "DEBUG: medical | ID: 7" instead of proper categorization pills, and a course ID mismatch where TUNA course (ID 1) is being incorrectly treated as course ID 8 with a medical type.

The solution will focus on:
1. Removing debug output from the frontend
2. Ensuring correct course ID resolution
3. Fixing course type detection and categorization pill generation
4. Implementing proper error logging

## Architecture

The current architecture involves several components:

1. **TopicController**: Responsible for loading course and topic data
2. **Course Model**: Contains course type information and methods
3. **CourseTypeService**: Determines categorizations based on course type
4. **topics_for_course.blade.php**: Frontend view that displays topics and categorization pills
5. **dynamic-categorization.js**: JavaScript for client-side filtering

The issue appears to be in the interaction between these components, particularly in how course type is determined and how debug information is being displayed.

## Components and Interfaces

### TopicController

The TopicController's `forCourse` method needs to be fixed to:
- Ensure it loads the correct course by ID
- Remove any debug output that might be leaking to the frontend
- Properly pass course type information to the view

### topics_for_course.blade.php

The view template needs to be modified to:
- Remove any debug output statements
- Fix the course type detection logic
- Ensure categorization pills are generated correctly based on course type
- Fix the emergency override for Course 1 (TUNA) that's causing issues

### CourseTypeService

This service appears to be working correctly but we'll verify that:
- It correctly determines course type
- It generates appropriate categorizations based on course type
- It handles null/empty course types properly

### dynamic-categorization.js

The JavaScript file needs to be checked to ensure:
- It correctly reads course type from the DOM
- It properly filters topics based on selected categories
- It doesn't output any debug information to the console or DOM

## Data Models

No changes to data models are required. The existing Course model already has a 'type' field that can be either 'medical' or 'custom'.

## Error Handling

We'll implement proper error handling:
- Server-side errors will be logged using Laravel's logging system
- No debug information will be displayed to end users
- If course loading fails, a proper 404 page will be shown
- If categorization fails, a fallback to showing all topics without filtering will be implemented

## Testing Strategy

1. **Unit Testing**:
   - Test CourseTypeService to ensure it correctly determines categorizations
   - Test Course model methods related to course type

2. **Integration Testing**:
   - Test TopicController's forCourse method with different course IDs
   - Verify correct course loading and type determination

3. **End-to-End Testing**:
   - Test the complete flow from course selection to topic display
   - Verify categorization pills display correctly for both medical and custom courses
   - Test filtering functionality with different categories

4. **Manual Testing**:
   - Verify TUNA course (ID 1) loads correctly with proper categorization
   - Check that no debug information is visible on the page
   - Test filtering with different categories to ensure it works correctly
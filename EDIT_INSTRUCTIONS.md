# Study Goals Widget - Final Adjustments

## File: resources/views/profile.blade.php

### Change 1 - Title (Line ~1547)
**Find this:**
```html
<h5 class="mb-0" style="font-weight: 700; font-size: 1.4rem; color: #1e293b; letter-spacing: -0.02em;">
    This Week's Goal
</h5>
```

**Replace with:**
```html
<h5 class="mb-0" style="font-weight: 600; font-size: 1.3rem; color: #0f172a; letter-spacing: -0.01em;">
    This Week's Goal
</h5>
```

### Change 2 - Progress Bar (Line ~1578)
**Find this:**
```html
<div class="progress" style="height: 14px; border-radius: 12px; background: #e2e8f0; margin-bottom: 10px; box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);">
```

**Replace with:**
```html
<div class="progress" style="height: 10px; border-radius: 10px; background: #e2e8f0; margin-bottom: 10px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.08);">
```

Also update the inner progress bar radius:
**Find:**
```
border-radius: 12px;
```

**Replace with:**
```
border-radius: 10px;
```

## Result:
- Title: More readable, less bold, darker color
- Progress bar: Thinner (10px), cleaner look

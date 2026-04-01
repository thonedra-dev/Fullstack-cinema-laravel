{{--
    resources/views/branch_manager/partials/_schedule_preview.blade.php
    ─────────────────────────────────────────────────────────────────────
    Static HTML shell for the schedule preview panel.
    All content inside #smt-preview-body is rendered dynamically by setup_timetable.js.

    Renders as:
      ┌─────────────────────────────────────────────────────┐
      │ 📋 Schedule Preview          [N slots]  [Clear All] │
      ├─────────────────────────────────────────────────────┤
      │ 🏟 Theatre One                        [✕ Remove]    │
      │   🕐 02:00 PM → 04:05 PM              [✕]           │
      │     [Apr 10] [Apr 11] [Apr 12]                      │
      │   🕐 05:30 PM → 07:35 PM              [✕]           │
      │     [Apr 14]                                        │
      ├─────────────────────────────────────────────────────┤
      │ 🏟 Theatre Two                        [✕ Remove]    │
      │   🕐 07:00 PM → 09:05 PM              [✕]           │
      │     [Apr 15]                                        │
      └─────────────────────────────────────────────────────┘
--}}

<div class="smt-preview-section" id="smt-preview-section">

    <div class="smt-preview-header">
        <div class="smt-preview-header__left">
            <span class="smt-preview-title">📋 Schedule Preview</span>
            <span class="smt-preview-counter vc-hidden" id="smt-preview-counter">
                <span id="smt-preview-count">0</span> slot(s) staged
            </span>
        </div>
        <button type="button" id="smt-clear-all-btn" class="smt-clear-all-btn">
            🗑 Clear All
        </button>
    </div>

    <div id="smt-preview-body">
        <p class="smt-preview-empty" id="smt-preview-empty">
            No slots staged yet. Select a theatre, set a time, choose date(s), then click
            <strong>Add to Schedule</strong>.
        </p>
    </div>

</div>
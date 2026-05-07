{{-- resources/views/admin/pages/add_inmate.blade.php --}}

<link rel="stylesheet" href="{{ asset('css/admin/pages/add-inmate.css') }}">

<div class="page" id="page-add-inmate">

    <div class="page-header">
        <div class="ai-back-row">
            <button class="ai-back-btn" onclick="ShowPage('inmates')" title="Back to Registry">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
                Back to Registry
            </button>
        </div>
        <h1>Add New <span class="gold">Inmate</span></h1>
        <p>Fill out all three sections before submitting.</p>
    </div>

    {{-- ── STEP INDICATOR ── --}}
    <div class="ai-steps">
        <div class="ai-step active" data-step="1">
            <div class="ai-step-bubble">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <div class="ai-step-label">
                <span class="ai-step-num">Step 1</span>
                <span class="ai-step-name">Inmate Info</span>
            </div>
        </div>

        <div class="ai-step-connector"></div>

        <div class="ai-step" data-step="2">
            <div class="ai-step-bubble">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <rect x="3" y="4" width="18" height="18" rx="2"/>
                    <path d="M16 2v4M8 2v4M3 10h18"/>
                </svg>
            </div>
            <div class="ai-step-label">
                <span class="ai-step-num">Step 2</span>
                <span class="ai-step-name">Personal Profile</span>
            </div>
        </div>

        <div class="ai-step-connector"></div>

        <div class="ai-step" data-step="3">
            <div class="ai-step-bubble">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <div class="ai-step-label">
                <span class="ai-step-num">Step 3</span>
                <span class="ai-step-name">Criminal Record</span>
            </div>
        </div>
    </div>

    {{-- ── FORM ── --}}
    {{-- NOTE: enctype="multipart/form-data" is required for the mugshot file upload --}}
    <form id="addInmateForm" autocomplete="off" novalidate enctype="multipart/form-data">

        {{-- ══════════════════════════════════════════
             SECTION 1 — INMATE INFO
        ══════════════════════════════════════════ --}}
        <div class="ai-section active" id="ai-section-1">
            <div class="panel-card ai-card">
                <div class="ai-card-header">
                    <div class="ai-card-icon gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <h3>Inmate Information</h3>
                        <p class="ai-card-sub">Facility records and detention details.</p>
                    </div>
                </div>

                <div class="ai-card-body ai-card-body--step1">

                    {{-- ── LEFT: MUGSHOT UPLOADER ── --}}
                    <div class="ai-mugshot-col">
                        <div class="ai-field-group-label">Mugshot Photo</div>

                        <div class="ai-mugshot-preview ai-mugshot-preview--large" id="ai-mugshot-preview">
                            <svg id="ai-mugshot-placeholder" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="1.2" width="56" height="56"
                                 style="opacity:.35">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <img id="ai-mugshot-img" src="" alt="Mugshot preview"
                                 style="display:none;width:100%;height:100%;object-fit:cover;">
                        </div>

                        <div class="ai-mugshot-controls">
                            <label class="ai-mugshot-label" for="ai-mugshot">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" width="14" height="14">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                                Choose Photo
                            </label>
                            <input type="file" id="ai-mugshot" name="mugshot"
                                   accept="image/jpeg,image/png,image/webp"
                                   style="display:none">
                            <span id="ai-mugshot-filename" class="ai-mugshot-filename">No file chosen</span>
                            <button type="button" id="ai-mugshot-clear" class="ai-mugshot-clear"
                                    style="display:none" onclick="AiClearMugshot()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" width="12" height="12">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                                Remove
                            </button>
                            <p class="ai-mugshot-hint">JPG, PNG or WebP · max 5 MB · optional</p>
                        </div>
                    </div>

                    {{-- ── RIGHT: FORM FIELDS ── --}}
                    <div class="ai-fields-col">

                    <div class="ai-form-row">
                        <div class="ai-form-group">
                            <label for="ai-lastName">Last Name <span class="req">*</span></label>
                            <input type="text" id="ai-lastName" name="last_name" placeholder="Dela Cruz" required>
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-firstName">First Name <span class="req">*</span></label>
                            <input type="text" id="ai-firstName" name="first_name" placeholder="Juan" required>
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-middleName">Middle Name</label>
                            <input type="text" id="ai-middleName" name="middle_name" placeholder="Santos">
                        </div>
                    </div>

                    <div class="ai-form-row">

                        {{-- ══ ASSIGNED CELL — modal picker ══
                             Clicking the trigger button opens #ai-cell-modal.
                             The modal contains a live search input and a results list.
                             On selection the cell_id is written to the hidden #ai-cell input
                             and the confirmation badge is updated.
                             The inline dropdown and its associated CSS classes have been removed.
                        --}}
                        <div class="ai-form-group" id="ai-cell-search-wrap">
                            <label>Assigned Cell <span class="ai-optional-tag">(optional)</span></label>

                            {{-- Trigger button — shows current selection or a placeholder --}}
                            <button type="button"
                                    id="ai-cell-trigger"
                                    class="ai-cell-trigger"
                                    onclick="AiOpenCellModal()"
                                    aria-haspopup="dialog"
                                    aria-controls="ai-cell-modal">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" width="15" height="15" aria-hidden="true">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                    <polyline points="9 22 9 12 15 12 15 22"/>
                                </svg>
                                <span id="ai-cell-trigger-text">Select a cell…</span>
                                <svg class="ai-cell-trigger-chevron" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2" width="13" height="13" aria-hidden="true">
                                    <polyline points="6 9 12 15 18 9"/>
                                </svg>
                            </button>

                            {{-- Clear button — appears after a cell is chosen --}}
                            <button type="button"
                                    id="ai-cell-clear"
                                    class="ai-cell-clear-btn"
                                    title="Clear selection"
                                    style="display:none"
                                    onclick="AiClearCellSelection()">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2.5" width="11" height="11" aria-hidden="true">
                                    <line x1="18" y1="6" x2="6" y2="18"/>
                                    <line x1="6" y1="6" x2="18" y2="18"/>
                                </svg>
                                Clear
                            </button>

                            {{-- Confirmation badge shown after a cell is selected --}}
                            <div id="ai-cell-selected-badge"
                                 class="ai-cell-selected-badge"
                                 style="display:none"
                                 aria-live="polite">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" width="13" height="13" aria-hidden="true">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                                <span id="ai-cell-selected-text"></span>
                            </div>

                            {{-- Optional hint --}}
                            <p class="ai-field-hint">Cell assignment can be left unset and updated later.</p>

                            {{-- Hidden field — ONLY this value is submitted to the server.
                                 Holds the numeric PK (cells.id) used as a foreign key. --}}
                            <input type="hidden" id="ai-cell" name="cell_id">
                        </div>{{-- /.ai-form-group (cell) --}}

                        <div class="ai-form-group">
                            <label for="ai-status">Status <span class="req">*</span></label>
                            <div class="ai-select-wrapper">
                                <select id="ai-status" name="status" required>
                                    <option value="" disabled selected>Select status</option>
                                    <option value="new">New Inmate</option>
                                    <option value="transferred">Transferred</option>
                                    <option value="active">Active</option>
                                    <option value="hold">On Hold</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-detentionType">Detention Type <span class="req">*</span></label>
                            <div class="ai-select-wrapper">
                                <select id="ai-detentionType" name="detention_type" required>
                                    <option value="" disabled selected>Select type</option>
                                    <option value="sentenced">Sentenced</option>
                                    <option value="detained">Detained (PDL)</option>
                                    <option value="transferred">Transferred</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ai-form-row">
                        <div class="ai-form-group">
                            <label for="ai-admissionDate">Admission Date <span class="req">*</span></label>
                            <input type="date" id="ai-admissionDate" name="admission_date" required>
                        </div>
                        <div class="ai-form-row">
                            <div class="ai-form-group ai-form-group--full">
                                <label for="ai-secLvl">Security Level<span class="req">*</span></label>
                                <div class="ai-select-wrapper">
                                    <select id="ai-secLvl" name="security_lvl" required>
                                        <option value="" disabled selected>Select Level</option>
                                        <option value="normal">Standard Security</option>
                                        <option value="medium">Medium Security</option>
                                        <option value="max">maximum Security</option>
                                        <option value="extreme">Extreme Security</option>
                                        <option value="deathrow">Death Row</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-commitOrder">Commitment Order No.<span class="req">*</span></label>
                            <input type="text" id="ai-commitOrder" name="commitment_order" placeholder="e.g. CO-2024-00123" required>
                        </div>
                    </div>

                    <div class="ai-form-row">
                        <div class="ai-form-group ai-form-group--full">
                            <label for="ai-courtBranch">Issuing Court / Branch</label>
                            <input type="text" id="ai-courtBranch" name="court_branch" placeholder="e.g. RTC Branch 14, Cebu City">
                        </div>
                    </div>

                    </div>{{-- /.ai-fields-col --}}
                </div>{{-- /.ai-card-body--step1 --}}
            </div>{{-- /.panel-card --}}

            <div class="ai-nav">
                <span></span>
                <button type="button" class="btn-gold" onclick="AiNextStep(2)">
                    Next: Personal Profile
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             SECTION 2 — PERSONAL PROFILE
        ══════════════════════════════════════════ --}}
        <div class="ai-section" id="ai-section-2">
            <div class="panel-card ai-card">
                <div class="ai-card-header">
                    <div class="ai-card-icon gold">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                    </div>
                    <div>
                        <h3>Personal Profile</h3>
                        <p class="ai-card-sub">Biographical and identification details.</p>
                    </div>
                </div>

                <div class="ai-card-body">

                    <div class="ai-field-group-label">Demographics</div>
                    <div class="ai-form-row">
                        <div class="ai-form-group">
                            <label for="ai-dob">Date of Birth <span class="req">*</span></label>
                            <input type="date" id="ai-dob" name="dob" required>
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-age">Age</label>
                            <input type="number" id="ai-age" name="age" placeholder="Auto-calculated" min="1" max="120" readonly>
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-sex">Sex <span class="req">*</span></label>
                            <div class="ai-select-wrapper">
                                <select id="ai-sex" name="sex" required>
                                    <option value="" disabled selected>Select</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ai-form-row">
                        <div class="ai-form-group">
                            <label for="ai-nationality">Nationality<span class="req">*</span></label>
                            <select type="text" id="ai-nationality" name="nationality" placeholder="e.g. Filipino" required>
                                <option value="" disabled selected>Select</option>
                                @foreach($nationalities as $nationality)
                                    <option value="{{ $nationality }}">{{ $nationality }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-religion">Religion</label>
                            <input type="text" id="ai-religion" name="religion" placeholder="e.g. Roman Catholic">
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-civilStatus">Civil Status</label>
                            <div class="ai-select-wrapper">
                                <select id="ai-civilStatus" name="civil_status">
                                    <option value="" disabled selected>Select</option>
                                    <option value="single">Single</option>
                                    <option value="married">Married</option>
                                    <option value="widowed">Widowed</option>
                                    <option value="separated">Separated</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="ai-form-divider"></div>

                    <div class="ai-field-group-label">Contact & Address</div>
                    <div class="ai-form-row">
                        <div class="ai-form-group">
                            <label for="ai-phone">Contact Number</label>
                            <input type="tel" id="ai-phone" name="phone" placeholder="e.g. 09XX-XXX-XXXX">
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-email">Email Address</label>
                            <input type="email" id="ai-email" name="email" placeholder="optional">
                        </div>
                    </div>
                    <div class="ai-form-row">
                        <div class="ai-form-group ai-form-group--full">
                            <label for="ai-homeAddress">Home Address <span class="req">*</span></label>
                            <input type="text" id="ai-homeAddress" name="home_address" placeholder="Street, Barangay, City / Municipality, Province" required>
                        </div>
                    </div>

                    <div class="ai-form-divider"></div>

                    <div class="ai-field-group-label">Government IDs</div>
                    <div class="ai-form-row">
                        <div class="ai-form-group">
                            <label for="ai-sss">SSS Number</label>
                            <input type="text" id="ai-sss" name="sss_number" placeholder="XX-XXXXXXX-X">
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-philhealth">PhilHealth Number</label>
                            <input type="text" id="ai-philhealth" name="philhealth_number" placeholder="XXXXXXXXXXXX">
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-pagibig">Pag-IBIG Number</label>
                            <input type="text" id="ai-pagibig" name="pagibig_number" placeholder="XXXX-XXXX-XXXX">
                        </div>
                    </div>

                    <div class="ai-form-divider"></div>

                    <div class="ai-field-group-label">Emergency Contact</div>
                    <div class="ai-form-row">
                        <div class="ai-form-group">
                            <label for="ai-ecName">Full Name</label>
                            <input type="text" id="ai-ecName" name="ec_name" placeholder="Contact person's name">
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-ecRelation">Relationship</label>
                            <input type="text" id="ai-ecRelation" name="ec_relation" placeholder="e.g. Spouse, Parent">
                        </div>
                        <div class="ai-form-group">
                            <label for="ai-ecPhone">Contact Number</label>
                            <input type="tel" id="ai-ecPhone" name="ec_phone" placeholder="09XX-XXX-XXXX">
                        </div>
                    </div>
                </div>
            </div>

            <div class="ai-nav">
                <button type="button" class="ai-btn-ghost" onclick="AiNextStep(1)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Back
                </button>
                <button type="button" class="btn-gold" onclick="AiNextStep(3)">
                    Next: Criminal Record
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════════
             SECTION 3 — CRIMINAL OFFENSE (one-to-many)
        ══════════════════════════════════════════ --}}
        <div class="ai-section" id="ai-section-3">
            <div class="panel-card ai-card">

                <div class="ai-card-header">
                    <div class="ai-card-icon red">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>
                    <div style="flex:1">
                        <h3>Criminal Offense Record</h3>
                        <p class="ai-card-sub">Add one or more offenses for this inmate.</p>
                    </div>
                    <button type="button" class="btn-gold" id="ai-open-crime-form-btn" onclick="AiOpenCrimeForm()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        Add Crime
                    </button>
                </div>

                <div class="ai-card-body">

                    <div id="ai-crime-form-panel" class="ai-crime-form-panel" style="display:none">
                        <div class="ai-crime-form-header">
                            <h4 id="ai-crime-form-title">Add Offense</h4>
                        </div>
                        <div class="ai-crime-form-inner">

                            <div class="ai-field-group-label">Offense Details</div>
                            <div class="ai-form-row">
                                <div class="ai-form-group">
                                    <label>Crime / Offense Name <span class="req">*</span></label>
                                    <input type="text" id="cf-crimeName" placeholder="e.g. Robbery, Homicide">
                                </div>
                                <div class="ai-form-group">
                                    <label>Date of Offense <span class="req">*</span></label>
                                    <input type="date" id="cf-crimeDate">
                                </div>
                                <div class="ai-form-group">
                                    <label>Location of Offense</label>
                                    <input type="text" id="cf-crimeLocation" placeholder="City / Municipality">
                                </div>
                            </div>

                            <div class="ai-form-row">
                                <div class="ai-form-group ai-form-group--full">
                                    <label>Law / Statute Violated <span class="req">*</span></label>
                                    <input type="text" id="cf-lawOffended" placeholder="e.g. Revised Penal Code Art. 249, RA 9165">
                                </div>
                            </div>

                            <div class="ai-form-row">
                                <div class="ai-form-group ai-form-group--full">
                                    <label>Brief Description</label>
                                    <textarea id="cf-crimeDesc" rows="3" placeholder="Short summary of the incident..."></textarea>
                                </div>
                            </div>

                            <div class="ai-form-divider"></div>

                            <div class="ai-field-group-label">Sentencing</div>
                            <div class="ai-form-row">
                                <div class="ai-form-group">
                                    <label>Imprisonment (Years) <span class="req">*</span></label>
                                    <input type="number" id="cf-sentenceYears" placeholder="e.g. 12" min="0">
                                </div>
                                <div class="ai-form-group">
                                    <label>Imprisonment (Months)</label>
                                    <input type="number" id="cf-sentenceMonths" placeholder="e.g. 6" min="0" max="12">
                                </div>
                                <div class="ai-form-group">
                                    <label>Verdict Date</label>
                                    <input type="date" id="cf-verdictDate">
                                </div>
                            </div>

                            <div class="ai-form-row">
                                <div class="ai-form-group">
                                    <label>Case Number</label>
                                    <input type="text" id="cf-caseNumber" placeholder="e.g. Crim. Case No. 2024-001">
                                </div>
                                <div class="ai-form-group">
                                    <label>Prosecutor</label>
                                    <input type="text" id="cf-prosecutor" placeholder="Full name">
                                </div>
                                <div class="ai-form-group">
                                    <label>Presiding Judge</label>
                                    <input type="text" id="cf-judge" placeholder="Full name">
                                </div>
                            </div>

                            <div class="ai-form-divider"></div>

                            <div class="ai-field-group-label">
                                Victim(s)
                                <button type="button" class="ai-add-row-btn" onclick="AiAddVictimRow()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13">
                                        <line x1="12" y1="5" x2="12" y2="19"/>
                                        <line x1="5" y1="12" x2="19" y2="12"/>
                                    </svg>
                                    Add Victim / Witnesses
                                </button>
                            </div>
                            <div id="ai-victims-list"></div>
                            <p id="ai-no-victims" class="ai-empty-note">No victims added. Click "Add Victim" if applicable.</p>

                            <div class="ai-crime-form-actions">
                                <button type="button" class="ai-btn-ghost" onclick="AiCloseCrimeForm()">Cancel</button>
                                <button type="button" class="btn-gold" id="ai-save-crime-btn" onclick="AiSaveCrime()">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    Save Crime
                                </button>
                            </div>

                        </div>
                    </div>

                    <div id="ai-crimes-list"></div>

                    <p id="ai-no-crimes" class="ai-empty-note" style="padding: 20px 0 8px">
                        No offenses added yet. Click <strong>Add Crime</strong> to begin.
                    </p>

                    <div id="ai-crimes-hidden"></div>

                </div>
            </div>

            <div class="ai-nav">
                <button type="button" class="ai-btn-ghost" onclick="AiNextStep(2)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                    Back
                </button>
                <button type="submit" class="btn-gold ai-btn-submit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Submit Inmate Record
                </button>
            </div>
        </div>

    </form>
</div>

{{-- ══════════════════════════════════════════
     CELL PICKER MODAL
     Rendered outside <form> so it sits on top of everything.
     JS writes the chosen cell_id into #ai-cell (inside the form) on confirm.
══════════════════════════════════════════ --}}
<div id="ai-cell-modal"
     class="ai-cell-modal-overlay"
     role="dialog"
     aria-modal="true"
     aria-labelledby="ai-cell-modal-title"
     style="display:none"
     onclick="AiCellModalOverlayClick(event)">

    <div class="ai-cell-modal">

        {{-- Modal header --}}
        <div class="ai-cell-modal-header">
            <div class="ai-cell-modal-title-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" width="18" height="18" aria-hidden="true">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
                <h4 id="ai-cell-modal-title">Select Assigned Cell</h4>
            </div>
            <button type="button"
                    class="ai-cell-modal-close"
                    onclick="AiCloseCellModal()"
                    aria-label="Close cell picker">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" width="16" height="16" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Search input --}}
        <div class="ai-cell-modal-search-wrap">
            <svg class="ai-cell-modal-search-icon" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="2" width="15" height="15" aria-hidden="true">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text"
                   id="ai-cell-modal-search"
                   class="ai-cell-modal-search-input"
                   placeholder="Search by cell ID or type…"
                   autocomplete="off"
                   spellcheck="false"
                   aria-label="Search cells"
                   oninput="AiCellModalFilter(this.value)">
            <button type="button"
                    id="ai-cell-modal-search-clear"
                    class="ai-cell-modal-search-clear"
                    style="display:none"
                    aria-label="Clear search"
                    onclick="AiCellModalClearSearch()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" width="12" height="12" aria-hidden="true">
                    <line x1="18" y1="6" x2="6" y2="18"/>
                    <line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        {{-- Column headings --}}
        <div class="ai-cell-modal-col-heads">
            <span>Cell ID</span>
            <span>Type</span>
            <span>Occupancy</span>
        </div>

        {{-- Results list — populated by JS --}}
        <ul id="ai-cell-modal-list"
            class="ai-cell-modal-list"
            role="listbox"
            aria-label="Available cells">
            <li class="ai-cell-modal-loading">
                <svg class="ai-cell-modal-spinner" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" width="18" height="18">
                    <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83
                             M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                </svg>
                Loading cells…
            </li>
        </ul>

        {{-- Modal footer --}}
        <div class="ai-cell-modal-footer">
            <span id="ai-cell-modal-count" class="ai-cell-modal-count"></span>
            <button type="button" class="ai-btn-ghost" onclick="AiCloseCellModal()">Cancel</button>
        </div>

    </div>
</div>
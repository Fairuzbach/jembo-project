# Approval Flow Diagram & User Guide

## 1. APPROVAL FLOW DIAGRAM

```
┌─────────────────────┐
│  USER CREATES WO    │
└──────────┬──────────┘
           │
           ▼
    ┌──────────────┐
    │ IS GA ADMIN? │
    └──┬───────┬───┘
       │ YES   │ NO
       ▼       ▼
    [PENDING]  [WAITING_APPROVAL_SPV]
       ↓           ↓
       │       ┌────────────────┐
       │       │ SPV DECISION   │
       │       └────┬────────┬──┘
       │            │        │
       │        APPROVE   REJECT
       │            │        │
       │            ▼        ▼
       │      [WAITING_    [REJECTED]
       │      APPROVAL_GA]
       │            │
       │       ┌────▼─────────┐
       │       │ GA ADMIN     │
       │       │ DECISION     │
       │       └────┬────────┬┘
       │            │        │
       │        APPROVE   REJECT
       │            │        │
       └────────┬───┘        │
                │            │
                ▼            ▼
           [PENDING] ──────► [WORK STARTS]
```

## 2. USER ROLES & ACTIONS

### A. REGULAR USER (User Biasa)

```
┌─────────────────────────┐
│  Action: Create WO      │
├─────────────────────────┤
│ Result: waiting_approval_spv │
│ Next: Wait for SPV      │
└─────────────────────────┘
```

- Membuat work order baru
- Status otomatis: `waiting_approval_spv`
- Melihat: Hanya WO milik divisi mereka
- Tidak bisa: Approve atau reject

### B. SUPERVISOR / MANAGER (SPV)

```
┌──────────────────────────────────┐
│ Action: Review & Decision         │
├──────────────────────────────────┤
│ View: WO waiting_approval_spv     │
│       dari divisi mereka          │
│                                  │
│ Decision: APPROVE or REJECT       │
├──────────────────────────────────┤
│ If APPROVE:                       │
│   - Status → waiting_approval_ga  │
│   - Email → GA Admin              │
│                                  │
│ If REJECT:                        │
│   - Status → rejected             │
│   - Email → Creator               │
└──────────────────────────────────┘
```

### C. GA ADMIN (General Affair Admin)

```
┌──────────────────────────────────────────┐
│ Action: Final Review & Approval          │
├──────────────────────────────────────────┤
│ View: ALL WO (all statuses)              │
│                                          │
│ Can Handle:                              │
│ 1. waiting_approval_spv WO               │
│    → Langsung approve ke [pending]       │
│    (Skip SPV step)                       │
│                                          │
│ 2. waiting_approval_ga WO                │
│    → Approve ke [pending]                │
│    (Final approval)                      │
├──────────────────────────────────────────┤
│ Dashboard Stats:                         │
│ ✓ Total Tiket                           │
│ ✓ Pending (ready to work)               │
│ ✓ Waiting Approval SPV (tahap 1)        │
│ ✓ Waiting Approval GA (tahap 2)         │
│ ✓ On Progress                           │
│ ✓ Rejected                              │
│ ✓ Selesai                               │
└──────────────────────────────────────────┘
```

## 3. SCREEN FLOW

### STEP 1: User Creates WO

```
User views: GA Work Order Dashboard
User clicks: "+ Create New Request"
User fills: Form (plant, dept, description, etc)
User submits: Form
System creates: WO with status = waiting_approval_spv
System sends: Email to SPV of requester's division
Result: ✓ WO created successfully
```

### STEP 2: SPV Reviews

```
SPV logs in
SPV views: GA Dashboard → "Waiting Approval SPV" card shows count
SPV clicks: Filter by status = "waiting_approval_spv"
SPV reviews: All WO from their division
SPV selects: One WO to review
SPV clicks: "Approve" or "Reject" button

If APPROVE:
  - Submits with decision
  - Status → waiting_approval_ga
  - Email → GA Admin
  - ✓ SPV approval recorded

If REJECT:
  - Fills rejection reason
  - Status → rejected
  - Email → Original requester
  - ✓ WO rejected
```

### STEP 3: GA Admin Final Approval

```
GA Admin logs in
GA Admin views: Dashboard
  - Sees: "Waiting Approval SPV: X" cards
  - Sees: "Waiting Approval GA: Y" cards

GA Admin chooses:
  Option A: Click "Waiting Approval GA" card
    - See only status=waiting_approval_ga
    - Review and APPROVE
    - Status → pending
    - ✓ Final approval given

  Option B: Click "Waiting Approval SPV" card
    - See status=waiting_approval_spv
    - Can SKIP SPV step and approve directly
    - Status → pending (no ga notification)
    - ✓ Quick approval path
```

### STEP 4: Work Execution

```
After GA approves → Status: pending
GA Team sees: Ticket in "Pending" status
GA Team clicks: "Process" to start work
Status changes: in_progress
Complete work and upload completion photos
Status changes: completed
```

## 4. DATABASE STATE TRACKING

### WO Status Transitions

```
waiting_approval_spv:
  - approved_spv_by: NULL
  - approved_spv_at: NULL
  - approved_ga_by: NULL
  - approved_ga_at: NULL
  - processed_by: NULL

                ↓ SPV APPROVES

waiting_approval_ga:
  - approved_spv_by: SPV_USER_ID
  - approved_spv_at: 2026-01-20 10:30:00
  - approved_ga_by: NULL
  - approved_ga_at: NULL
  - processed_by: SPV_USER_ID

                ↓ GA ADMIN APPROVES

pending:
  - approved_spv_by: SPV_USER_ID
  - approved_spv_at: 2026-01-20 10:30:00
  - approved_ga_by: GA_ADMIN_ID
  - approved_ga_at: 2026-01-20 11:00:00
  - processed_by: GA_ADMIN_ID
```

## 5. FILTER OPTIONS

Users can filter WO by status:

```
Dashboard → Filter Panel → Status dropdown

Available options:
✓ pending
✓ waiting_approval_spv    [NEW]
✓ waiting_approval_ga     [NEW]
✓ in_progress
✓ completed
✓ cancelled
✓ rejected
```

## 6. EMAIL NOTIFICATIONS

### When WO Created

```
TO: Original Requester
SUBJECT: Your Work Order has been created
MESSAGE: Your WO has been submitted and is waiting for supervisor approval
```

### When SPV Approves

```
TO: GA Admin
SUBJECT: Work Order Approved by Supervisor
MESSAGE: WO #GA-20260120-0001 has been approved by SPV and is waiting your final review
ACTION: Click to view and approve
```

### When GA Admin Approves

```
TO: Original Requester
SUBJECT: Your Work Order has been Approved!
MESSAGE: Your request has been fully approved and is now in the queue for processing
```

### When Rejected

```
TO: Original Requester
SUBJECT: Your Work Order has been Rejected
MESSAGE: Your request has been rejected
REASON: [Rejection reason from approver]
```

## 7. COMMON SCENARIOS

### Scenario A: Normal 2-Step Approval

```
User (Dept A) → Creates WO
        ↓
SPV (Dept A) → Reviews → Approves
        ↓
GA Admin → Reviews → Approves
        ↓
Status: pending → Ready to work
```

### Scenario B: GA Admin Skip SPV

```
User (Dept A) → Creates WO (waiting_approval_spv)
        ↓
GA Admin → See status waiting_approval_spv
        ↓
GA Admin → Click Approve (Skip SPV)
        ↓
Status: pending → Ready to work immediately
```

### Scenario C: Rejection at SPV Level

```
User → Creates WO (waiting_approval_spv)
  ↓
SPV → Reviews → Rejects
  ↓
Status: rejected
  ↓
User can create new WO or contact SPV for details
```

### Scenario D: Rejection at GA Level

```
User → Creates WO
  ↓
SPV → Reviews → Approves (waiting_approval_ga)
  ↓
GA Admin → Reviews → Rejects
  ↓
Status: rejected
  ↓
User needs to contact GA Admin or SPV
```

## 8. TROUBLESHOOTING

### Issue: User can't see their WO in dashboard

**Solution**: Check status. If `waiting_approval_spv`, user still needs SPV to approve

### Issue: SPV doesn't see pending WO

**Solution**:

- Check if user selected correct department
- Check if SPV's role is correctly set (spv, supervisor, manager, dept_head)

### Issue: GA Admin doesn't see WO

**Solution**:

- GA Admin should see ALL statuses
- Check if user's role is ga.admin

### Issue: Email not being sent

**Solution**:

- Check SMTP configuration in .env
- Check if email addresses are valid in user records
- Check logs: `storage/logs/laravel.log`

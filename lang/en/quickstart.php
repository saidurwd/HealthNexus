<?php

// One-page role quick-start cards (guideline 19.7). Each role: a title, what you do all day, six steps, two tips.
return [
    'receptionist' => [
        'title' => 'Receptionist',
        'goal' => 'Register patients, book appointments and keep the queue moving.',
        'steps' => [
            'Press Ctrl+K and type the patient\'s mobile number, patient number or name. Scanning a wristband also works.',
            'Not found? Choose Patients → Register Patient. Fill in the name, mobile number and date of birth, then press Check Duplicates before saving.',
            'Choose Appointments → New Appointment. Pick the doctor, the day and a free time.',
            'When the patient arrives, open the appointment and press Check-in. They join the doctor\'s queue.',
            'Print the appointment slip for the patient.',
            'Use the Doctor Queue page to see who is waiting and for how long.',
        ],
        'tips' => ['Always search before you register: a second record for the same person is the most common mistake.', 'If the system is down, use the paper forms from Administration → Downtime kit.'],
    ],
    'doctor' => [
        'title' => 'Doctor',
        'goal' => 'See your patients, record the consultation and prescribe.',
        'steps' => [
            'Sign in. You land on your queue. Check the patient\'s banner first: allergies and alerts are always shown at the top.',
            'Press Start Consultation on the next patient.',
            'Record vital signs, complaints and the diagnosis.',
            'Add medicines in Add Prescription and tests in Add Investigation Order. Read any safety warning before you continue.',
            'Press Complete Consultation. Book a follow-up if needed.',
            'Print the prescription (A5) for the patient.',
        ],
        'tips' => ['A warning about an allergy needs a reason if you decide to continue. The reason is recorded.', 'A locked record can only be opened with Break Glass in an emergency; every use is reviewed.'],
    ],
    'nurse' => [
        'title' => 'Nurse',
        'goal' => 'Care for your ward patients, give medicines safely and hand over clearly.',
        'steps' => [
            'Sign in. You land on My Patients. Open a patient to see tasks that are due.',
            'Record vital signs and observations. A value outside the safe range raises an alert for you and your charge nurse.',
            'Before giving a medicine, check the patient, the medicine, the dose, the route and the time. Scan the wristband if you have a scanner.',
            'Press Administer, or record why it was held, refused or omitted.',
            'Record intake and output, wounds and devices as they change.',
            'At the end of the shift, press Prepare Handover. The next nurse acknowledges it.',
        ],
        'tips' => ['Acknowledge every alert: unacknowledged alerts are passed up to the charge nurse automatically.', 'If a mistake was recorded, use Record Correction. Nothing is deleted.'],
    ],
    'lab' => [
        'title' => 'Laboratory',
        'goal' => 'Collect and receive specimens, enter results and release reports.',
        'steps' => [
            'Open the Lab worklist. Orders are sorted by priority.',
            'Press Collect Specimen and print the label. Scan or type the number on the tube when it arrives.',
            'Reject a bad specimen with a reason; the doctor is told to send a new one.',
            'Enter results. Values outside the reference range are flagged.',
            'A critical result must be telephoned and acknowledged: the doctor confirms by reading it back.',
            'The pathologist approves and the report is released.',
        ],
        'tips' => ['Never edit a released report: use Amend. The old version stays visible.', 'The label number is the only link between tube and patient. Check it twice.'],
    ],
    'radiology' => [
        'title' => 'Radiology',
        'goal' => 'Schedule examinations, record them and get reports out.',
        'steps' => [
            'Open the Radiology dashboard and the Examination Queue.',
            'Check the patient in. Confirm the procedure, the body part and any preparation or allergy to contrast.',
            'Press Start Examination, then Complete Examination.',
            'Check for Images to link the PACS study.',
            'The radiologist writes the report and submits it for approval.',
            'A critical finding is flagged with Flag Critical Finding so the doctor is told straight away.',
        ],
        'tips' => ['Pregnancy and contrast allergy questions are asked every time.', 'An amended report keeps the earlier version.'],
    ],
    'pharmacist' => [
        'title' => 'Pharmacist',
        'goal' => 'Check prescriptions, dispense safely and keep stock accurate.',
        'steps' => [
            'Sign in. You land on the prescription queue.',
            'Open a prescription. Read the allergy and interaction warnings before anything else.',
            'Match each line to a medicine. Tick the lines you will dispense.',
            'Press Dispense. The system chooses the batch that expires first.',
            'If you substitute a medicine, write the reason.',
            'At closing, check the controlled-drug register and any near-expiry batches.',
        ],
        'tips' => ['Stock changes only through receive, dispense, transfer, return or adjustment, and each leaves a record.', 'A recalled batch is quarantined automatically; do not dispense it.'],
    ],
    'cashier' => [
        'title' => 'Cashier',
        'goal' => 'Collect payments and balance your cash drawer.',
        'steps' => [
            'Open your session: Billing → My Session → Open Session. Enter the cash in the drawer.',
            'Find the patient (Ctrl+K) and open the invoice.',
            'Press Collect Payment, choose cash, card or mobile banking and enter the amount.',
            'Print the 80 mm receipt. A second copy needs a reason and is marked DUPLICATE.',
            'For a refund or a discount, use Request Refund or Request Adjustment; a supervisor approves it.',
            'At the end of the day, Close Session and count the cash. Explain any difference.',
        ],
        'tips' => ['Never collect money outside a session.', 'If the system is down, write a downtime receipt and enter it later.'],
    ],
    'manager' => [
        'title' => 'Manager',
        'goal' => 'See how the hospital is doing and act on what needs attention.',
        'steps' => [
            'Sign in. The Dashboard shows today\'s key numbers.',
            'Open a module dashboard (Billing, IPD, Laboratory…) for detail.',
            'Check the Critical Alerts list and anything unacknowledged.',
            'Review the bed board and delayed discharges.',
            'Export a report: it is prepared in the background and the file is removed after one day.',
            'Review the Audit logs and Break-glass uses regularly.',
        ],
        'tips' => ['Exports contain patient information. Share them only with people who need them.', 'The Downtime kit page shows the latest inpatient census.'],
    ],
];

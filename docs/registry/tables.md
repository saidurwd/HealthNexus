# Table Registry

_Generated from the Schema::create calls in database/migrations._

## appointment (14)

- `appointment_documents`
- `appointment_notes`
- `appointment_number_counters`
- `appointment_reminder_rules`
- `appointment_reminders`
- `appointment_rooms`
- `appointment_slots`
- `appointment_status_histories`
- `appointment_tokens`
- `appointment_types`
- `appointments`
- `doctor_schedules`
- `provider_unavailability`
- `providers`

## billing (23)

- `billing_adjustments`
- `billing_advance_accounts`
- `billing_advance_transactions`
- `billing_cashier_sessions`
- `billing_categories`
- `billing_charges`
- `billing_corporate_contracts`
- `billing_corporate_members`
- `billing_corporates`
- `billing_counters`
- `billing_insurance_policies`
- `billing_insurance_providers`
- `billing_invoice_items`
- `billing_invoices`
- `billing_items`
- `billing_payment_methods`
- `billing_payments`
- `billing_price_list_items`
- `billing_price_lists`
- `billing_receipts`
- `billing_refunds`
- `billing_tax_categories`
- `charge_event_log`

## bloodbank (19)

- `blood_crossmatches`
- `blood_discards`
- `blood_donations`
- `blood_donor_adverse_events`
- `blood_donor_screenings`
- `blood_donors`
- `blood_incidents`
- `blood_issues`
- `blood_recalls`
- `blood_requests`
- `blood_reservations`
- `blood_samples`
- `blood_storage_locations`
- `blood_temperature_logs`
- `blood_transfusion_reactions`
- `blood_transfusions`
- `blood_unit_events`
- `blood_unit_tests`
- `blood_units`

## core (52)

- `activity_logs`
- `audit_logs`
- `backup_runs`
- `branches`
- `break_glass_accesses`
- `cache`
- `cache_locks`
- `code_concepts`
- `code_maps`
- `code_systems`
- `countries`
- `currencies`
- `departments`
- `document_prints`
- `document_templates`
- `downtime_censuses`
- `failed_jobs`
- `file_versions`
- `files`
- `genders`
- `hospital_holidays`
- `hospitals`
- `identification_types`
- `import_batches`
- `import_rows`
- `job_batches`
- `jobs`
- `login_histories`
- `marital_statuses`
- `notifications`
- `number_allocations`
- `number_series`
- `organization_modules`
- `organizations`
- `password_reset_tokens`
- `patient_search_index`
- `personal_access_tokens`
- `report_exports`
- `security_events`
- `sessions`
- `settings`
- `specialties`
- `states`
- `user_branches`
- `user_departments`
- `user_organizations`
- `users`
- `workflow_actions`
- `workflow_approvers`
- `workflow_instances`
- `workflow_steps`
- `workflows`

## ed (15)

- `ed_bay_assignments`
- `ed_bays`
- `ed_consults`
- `ed_dispositions`
- `ed_injuries`
- `ed_mci_activations`
- `ed_medication_administrations`
- `ed_observation_stays`
- `ed_protocol_runs`
- `ed_protocol_steps`
- `ed_resus_events`
- `ed_status_history`
- `ed_triage_records`
- `ed_visits`
- `ed_zones`

## hr (40)

- `hr_appraisal_cycles`
- `hr_appraisals`
- `hr_attendance_days`
- `hr_candidates`
- `hr_checklist_items`
- `hr_checklists`
- `hr_contracts`
- `hr_credentials`
- `hr_dependents`
- `hr_devices`
- `hr_documents`
- `hr_employee_history`
- `hr_employees`
- `hr_jobs`
- `hr_leave_ledger`
- `hr_leave_requests`
- `hr_leave_types`
- `hr_loan_entries`
- `hr_loans`
- `hr_overtime_entries`
- `hr_pay_components`
- `hr_payment_batches`
- `hr_payroll_adjustments`
- `hr_payroll_line_items`
- `hr_payroll_lines`
- `hr_payroll_periods`
- `hr_payroll_runs`
- `hr_positions`
- `hr_provider_links`
- `hr_punches`
- `hr_regularizations`
- `hr_roster_entries`
- `hr_salary_structure_lines`
- `hr_salary_structures`
- `hr_shifts`
- `hr_staffing_requirements`
- `hr_tax_slabs`
- `hr_training_records`
- `hr_trainings`
- `hr_vacancies`

## icu (26)

- `icu_admission_requests`
- `icu_alert_escalations`
- `icu_assessments`
- `icu_charge_events`
- `icu_clinical_score_results`
- `icu_counters`
- `icu_critical_alerts`
- `icu_critical_events`
- `icu_device_assessments`
- `icu_devices`
- `icu_discharge_summaries`
- `icu_episode_events`
- `icu_episodes`
- `icu_family_communications`
- `icu_fluid_balance_entries`
- `icu_handover_records`
- `icu_infusion_titrations`
- `icu_infusions`
- `icu_nutrition_records`
- `icu_observations`
- `icu_procedure_records`
- `icu_procedures`
- `icu_progress_note_amendments`
- `icu_progress_notes`
- `icu_respiratory_support`
- `icu_ventilator_events`

## insurance (20)

- `corporate_batch_invoices`
- `corporate_batches`
- `insurance_appeals`
- `insurance_claim_documents`
- `insurance_claim_events`
- `insurance_claim_lines`
- `insurance_claim_queries`
- `insurance_claims`
- `insurance_contract_rates`
- `insurance_eligibility_checks`
- `insurance_payers`
- `insurance_plan_rules`
- `insurance_plans`
- `insurance_policy_dependents`
- `insurance_preauths`
- `insurance_remittance_lines`
- `insurance_remittances`
- `insurance_submission_claims`
- `insurance_submissions`
- `insurance_writeoffs`

## interop (11)

- `dicom_worklist_entries`
- `hl7_inbound_results`
- `integration_access_log`
- `integration_attempts`
- `integration_credentials`
- `integration_endpoints`
- `integration_id_maps`
- `integration_messages`
- `integration_nonces`
- `integration_partners`
- `integration_routes`

## inventory (41)

- `inventory_adjustments`
- `inventory_batches`
- `inventory_categories`
- `inventory_consumptions`
- `inventory_count_lines`
- `inventory_counts`
- `inventory_issue_lines`
- `inventory_issues`
- `inventory_item_uoms`
- `inventory_items`
- `inventory_locations`
- `inventory_recalls`
- `inventory_request_lines`
- `inventory_requests`
- `inventory_serials`
- `inventory_stock`
- `inventory_stores`
- `inventory_transfer_lines`
- `inventory_transfers`
- `procurement_approvals`
- `procurement_budgets`
- `procurement_contracts`
- `procurement_goods_receipts`
- `procurement_grn_lines`
- `procurement_po_amendments`
- `procurement_po_lines`
- `procurement_purchase_orders`
- `procurement_quotation_lines`
- `procurement_quotations`
- `procurement_requisition_lines`
- `procurement_requisitions`
- `procurement_rfq_lines`
- `procurement_rfq_suppliers`
- `procurement_rfqs`
- `procurement_supplier_documents`
- `procurement_supplier_evaluations`
- `procurement_supplier_invoice_lines`
- `procurement_supplier_invoices`
- `procurement_supplier_return_lines`
- `procurement_supplier_returns`
- `procurement_suppliers`

## ipd (20)

- `ipd_admission_requests`
- `ipd_admission_sources`
- `ipd_admission_types`
- `ipd_admissions`
- `ipd_bed_allocations`
- `ipd_bed_blocks`
- `ipd_bed_charge_events`
- `ipd_bed_movements`
- `ipd_bed_reservations`
- `ipd_bed_types`
- `ipd_beds`
- `ipd_buildings`
- `ipd_counters`
- `ipd_discharge_dispositions`
- `ipd_discharge_requests`
- `ipd_floors`
- `ipd_patient_leaves`
- `ipd_provider_assignments`
- `ipd_rooms`
- `ipd_wards`

## lab (20)

- `lab_analyzer_tests`
- `lab_analyzers`
- `lab_container_types`
- `lab_counters`
- `lab_critical_result_alerts`
- `lab_critical_values`
- `lab_order_items`
- `lab_orders`
- `lab_panel_items`
- `lab_panels`
- `lab_qc_materials`
- `lab_qc_runs`
- `lab_reference_ranges`
- `lab_reports`
- `lab_results`
- `lab_sections`
- `lab_specimen_types`
- `lab_specimens`
- `lab_test_categories`
- `lab_tests`

## nursing (27)

- `nursing_alerts`
- `nursing_assessments`
- `nursing_assignments`
- `nursing_care_plan_goals`
- `nursing_care_plan_interventions`
- `nursing_care_plans`
- `nursing_device_assessments`
- `nursing_devices`
- `nursing_diagnoses`
- `nursing_discharge_checklists`
- `nursing_education`
- `nursing_episodes`
- `nursing_escalations`
- `nursing_handover`
- `nursing_handover_items`
- `nursing_intake_output_records`
- `nursing_iv_infusions`
- `nursing_medication_administration_corrections`
- `nursing_medication_administrations`
- `nursing_note_amendments`
- `nursing_notes`
- `nursing_observations`
- `nursing_pain_assessments`
- `nursing_risk_assessments`
- `nursing_shifts`
- `nursing_tasks`
- `nursing_wound_assessments`

## opd (34)

- `clinical_alert_escalations`
- `clinical_alert_recipients`
- `clinical_alerts`
- `clinical_number_counters`
- `clinical_observation_sets`
- `clinical_observations`
- `clinical_order_items`
- `clinical_orders`
- `diagnoses`
- `encounter_allergies`
- `encounter_amendments`
- `encounter_assessments`
- `encounter_complaints`
- `encounter_counters`
- `encounter_documents`
- `encounter_examinations`
- `encounter_follow_ups`
- `encounter_histories`
- `encounter_instructions`
- `encounter_medication_histories`
- `encounter_notes`
- `encounter_procedures`
- `encounter_referrals`
- `encounter_review_of_systems`
- `encounter_status_history`
- `encounter_template_sections`
- `encounter_templates`
- `encounter_types`
- `encounter_vitals`
- `encounters`
- `investigation_orders`
- `prescription_items`
- `prescriptions`
- `vital_signs`

## ot (23)

- `ot_complexes`
- `ot_counters`
- `ot_implants`
- `ot_operative_note_amendments`
- `ot_operative_notes`
- `ot_preoperative_assessments`
- `ot_preoperative_checklist_items`
- `ot_preoperative_checklists`
- `ot_procedure_categories`
- `ot_procedures`
- `ot_recovery_records`
- `ot_specimens`
- `ot_surgeries`
- `ot_surgery_procedures`
- `ot_surgery_requests`
- `ot_surgery_schedule_events`
- `ot_surgical_complications`
- `ot_surgical_counts`
- `ot_surgical_safety_checklist_items`
- `ot_surgical_safety_checklists`
- `ot_surgical_team`
- `ot_theatre_capabilities`
- `ot_theatres`

## patient (19)

- `patient_addresses`
- `patient_alerts`
- `patient_allergies`
- `patient_amendments`
- `patient_branch_registrations`
- `patient_consents`
- `patient_contacts`
- `patient_documents`
- `patient_duplicate_candidates`
- `patient_guardians`
- `patient_histories`
- `patient_identifiers`
- `patient_number_counters`
- `patient_portal_accounts`
- `patient_preferences`
- `patient_problems`
- `patient_timeline_events`
- `patient_types`
- `patients`

## pharmacy (27)

- `pharmacy_batches`
- `pharmacy_brands`
- `pharmacy_controlled_drug_transactions`
- `pharmacy_counters`
- `pharmacy_dispensing_items`
- `pharmacy_dispensings`
- `pharmacy_dosage_forms`
- `pharmacy_generics`
- `pharmacy_medication_ingredients`
- `pharmacy_medication_store_levels`
- `pharmacy_medications`
- `pharmacy_order_items`
- `pharmacy_orders`
- `pharmacy_quarantine`
- `pharmacy_recalls`
- `pharmacy_return_items`
- `pharmacy_returns`
- `pharmacy_routes`
- `pharmacy_safety_alerts`
- `pharmacy_stock`
- `pharmacy_stock_count_items`
- `pharmacy_stock_counts`
- `pharmacy_stock_transactions`
- `pharmacy_stores`
- `pharmacy_transfer_items`
- `pharmacy_transfers`
- `stock_ledger`

## portal (27)

- `crm_campaign_recipients`
- `crm_campaigns`
- `crm_complaint_events`
- `crm_complaints`
- `crm_feedback`
- `crm_form_submissions`
- `crm_forms`
- `crm_interactions`
- `crm_segments`
- `portal_access_log`
- `portal_comm_preferences`
- `portal_devices`
- `portal_documents`
- `portal_messages`
- `portal_notifications`
- `portal_otps`
- `portal_outbox`
- `portal_payments`
- `portal_proxies`
- `portal_refill_requests`
- `portal_release_policies`
- `portal_result_releases`
- `portal_security_events`
- `portal_service_requests`
- `portal_settlement_lines`
- `portal_settlements`
- `portal_threads`

## radiology (19)

- `radiology_body_parts`
- `radiology_contrast_agents`
- `radiology_counters`
- `radiology_critical_findings`
- `radiology_dicom_events`
- `radiology_examinations`
- `radiology_instances`
- `radiology_modalities`
- `radiology_order_items`
- `radiology_orders`
- `radiology_pacs_servers`
- `radiology_procedures`
- `radiology_protocols`
- `radiology_report_findings`
- `radiology_report_templates`
- `radiology_reports`
- `radiology_sections`
- `radiology_series`
- `radiology_studies`

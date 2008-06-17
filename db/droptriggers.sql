-- drop all triggers and stored procedures from customer database
-- used by customer data migration script for release 6.0

drop trigger insert_repeating_job
$$$
drop trigger update_job
$$$
drop trigger delete_job
$$$
drop trigger insert_jobsetting
$$$
drop trigger update_jobsetting
$$$
drop trigger delete_jobsetting
$$$
drop trigger insert_schedule
$$$
drop trigger update_schedule
$$$
drop trigger delete_schedule
$$$
drop trigger insert_reportsubscription
$$$
drop trigger update_reportsubscription
$$$
drop trigger delete_reportsubscription
$$$
drop procedure start_import
$$$
drop procedure start_specialtask
$$$



-- drop all triggers and stored procedures from customer database
-- used by customer data migration script for release 6.0

drop trigger if exists insert_repeating_job
$$$
drop trigger if exists update_job
$$$
drop trigger if exists delete_job
$$$
drop trigger if exists insert_jobsetting
$$$
drop trigger if exists update_jobsetting
$$$
drop trigger if exists delete_jobsetting
$$$
drop trigger if exists insert_schedule
$$$
drop trigger if exists update_schedule
$$$
drop trigger if exists delete_schedule
$$$
drop trigger if exists insert_reportsubscription
$$$
drop trigger if exists update_reportsubscription
$$$
drop trigger if exists delete_reportsubscription
$$$
drop procedure if exists start_import
$$$
drop procedure if exists start_specialtask
$$$
drop trigger if exists insert_joblist
$$$
drop trigger if exists update_joblist
$$$
drop trigger if exists delete_joblist
$$$



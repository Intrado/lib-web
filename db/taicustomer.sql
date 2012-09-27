-- This file contains product specific schema for TAI

INSERT INTO `setting` (`name`, `value`) VALUES ('_dbtaiversion', '0.1/1')
$$$

-- Topic is similar to the subject in an email. The topic table contains a list of preapproved topics that the sender has to pick from.
CREATE TABLE `tai_topic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
CREATE TABLE `tai_topicuser` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topicid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `isbcc` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$ 

CREATE TABLE `tai_organizationtopic` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `topicid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Thread is a conversation between a sender and a recipient, with CCs based on topic. New threads can be forked from another thread, however the originating user of the first thread should be maintained in the originatinguserid field, along with wassentanonymously.
CREATE TABLE `tai_thread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `originatinguserid` int(11) NOT NULL,
  `recipientuserid` int(11) NOT NULL,
  `topicid` int(11) NOT NULL,
  `parentthreadid` int(11) DEFAULT NULL,
  `wassentanonymously` tinyint(4) NOT NULL DEFAULT '0',
  `modifiedtimestamp` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Folders allow users to organize threads. Some folders will be created by the system, such as inbox (or unsorted), and trash. Trash is a first level delete; threads are moved to the trash before being deleted.
CREATE TABLE `tai_folder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `type` enum('sent','trash','custom') NOT NULL DEFAULT 'custom',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Userthread associate a thread to the involved users, both the sender and the recipient, and any CC based on topic. When a user deletes a thread (moves to trash, then deletes from there), the isdelete flag is set for this userthread, and all existing (but not future) usermessages.
CREATE TABLE `tai_userthread` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `userid` int(11) NOT NULL,
  `threadid` int(11) NOT NULL,
  `folderid` int(11) DEFAULT NULL,
  `isdeleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
-- Message always belong to a conversation. A message can not be a draft. Messages have a sender, and recipient. This is analogous to an email FROM and single TO, with CCs happening implicitly via the thread's topic.
CREATE TABLE `tai_message` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `threadid` int(11) NOT NULL,
  `senderuserid` int(11) NOT NULL,
  `recipientuserid` int(11) NOT NULL,
  `method` enum('web','sms') NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `body` mediumtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
 
-- Usermessage keeps track of user+message specific data, such as if that user has read this message, or deleted this message component of the thread.
CREATE TABLE `tai_usermessage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messageid` int(11) NOT NULL,
  `userid` int(11) NOT NULL,
  `isread` tinyint(4) NOT NULL DEFAULT '0',
  `isdeleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$
 
 
CREATE TABLE `tai_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `publishtimestamp` int(11) NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `body` text NOT NULL,
  `deleted` TINYINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8
$$$

-- $rev 2

CREATE TABLE `tai_cannedresponse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `topicid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `body` text NOT NULL,
  `enabled` TINYINT NOT NULL DEFAULT '1',
  `deleted` TINYINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
$$$

-- $rev 3
CREATE TABLE `tai_survey` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `url` varchar(255) NOT NULL,
  `modifiedtimestamp` int(11) NOT NULL,
  `deleted` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
$$$

-- $rev 4
CREATE TABLE `tai_lockout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `organizationid` int(11) NOT NULL,
  `title` text NOT NULL,
  `type` enum('hard','soft') NOT NULL DEFAULT 'hard',
  `starttimestamp` time NOT NULL,
  `endtimestamp` time NOT NULL,
  `days` varchar(255) NOT NULL,
  `messagebody` text NOT NULL,
  `deleted` TINYINT NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
$$$


-- $rev 5
alter table tai_thread modify topicid int(11)
$$$
alter table tai_thread add threadtype enum('thread', 'comment', 'identityreveal') not null default 'thread'
$$$            

-- $rev 6
alter table tai_userthread add column frombcc tinyint(4) not null default 0;
$$$

-- $rev 7
alter table tai_lockout add start_date DATE;
$$$
alter table tai_lockout add end_date DATE;
$$$

-- $rev 8
alter table tai_thread add identityrequested tinyint(4) not null default 0
$$$

update setting set value='0.1/8' where name='_dbtaiversion'
$$$

-- $rev 9
ALTER TABLE tai_userthread ADD INDEX `userfolder` ( `userid` , `folderid` ) 
$$$
ALTER TABLE tai_usermessage ADD INDEX `usermessage` ( `userid` , `messageid` )
$$$

update setting set value='0.1/9' where name='_dbtaiversion'
$$$
-- END 0.1/9

-- $rev 10
alter table tai_organizationtopic add enabled tinyint(4) not null default 1
$$$
ALTER TABLE tai_folder CHANGE `type` `type` ENUM( 'inbox', 'sent', 'trash', 'custom' ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'custom'
$$$

update setting set value='0.1/10' where name='_dbtaiversion'
$$$
-- END 0.1/10

-- $rev 11
ALTER TABLE `tai_organizationtopic` DROP `enabled`;
$$$
ALTER TABLE `tai_userthread` DROP `frombcc` ;
$$$

update setting set value='0.1/11' where name='_dbtaiversion'
$$$
-- END 0.1/11

-- Default Topics 
INSERT INTO tai_topic (`name`) VALUES 
('Abuse'),('Alcohol'),('Bullying'),('Bus Concerns'),('Cheating on Schoolwork'),('Cutting/Self-Injury'),('Cyber Bullying'),('Dating Violence'),('Depression'),('Discrimination'),('Dropping Out'),('Drugs'),('Eating Disorder'),('Family Problems'),('Fighting'),('Gangs'),('Health Issues'),('Peer Pressure'),('Pregnancy'),('Run Away'),('School Closing'),('Sexual Harassment'),('Smoking'),('Stealing/Theft'),('Stress'),('Suicide'),('Thank You/Compliment'),('Vandalism'),('Violence'),('Weapons')
$$$

-- Default Canned Responses, using temporary table to map topic names with their canned response
CREATE TEMPORARY TABLE tmp_cannedresponse (`topicname` varchar(50) NOT NULL DEFAULT '',	`title` varchar(50) NOT NULL,`description` varchar(255) NOT NULL,`body` text NOT NULL)
$$$
INSERT INTO tmp_cannedresponse (`topicname`, `title`, `description`, `body`) VALUES 
('Abuse','Relationship','Relationship','Thank you for trusting me with this information. This can be a very difficult and scary situation. I want to help you. Have you told anyone else about this situation? Would you be willing to meet with me? We can schedule a meeting before or after school if you would feel more comfortable.'),('Abuse','Family Member/Adult','Family Member/Adult','Thank you for trusting me with this information. This can be a very difficult and scary situation. I would like to help you. Would you be willing to meet with me?'),('Alcohol','Others','Others','Thank you for reporting this and trusting me with this information. Alcohol can be very dangerous and affect not only the user, but also others around. Can you provide me with additional details? I want to help you. Who is using this? Where and when is this occurring? This information is between you and me.'),('Alcohol','Sender','Sender','Thank you for reporting this and trusting me with this information. Alcohol can be very dangerous and affect not only the user, but also others around. How long has this been going on? What has caused you to turn to Alcohol? I am here to help.'),('Bullying','Others','Others','Bullying is a horrible action, and no one should have to put up with it. I am sorry that your classmate is dealing with this. You have made the right decision in telling a trusted adult. I am here to help. Remember this conversation will stay between you and me. Can you tell me more information about where this is occurring, so I can take the necessary steps to put an end to this?'),('Cheating on Schoolwork','Other is cheating or has cheated','Other is cheating or has cheated','Thank you for reporting this. Cheating is unfair and needs to stop. Can you give me information about when and where this is taking place? Remember this conversation is between you and me. I want to do something to help.'),('Cheating on Schoolwork','Sender confessing he/she cheated or is cheating','Sender confessing he/she cheated or is cheating','Thank you for admitting what you have done. Remember, everyone makes mistakes. It is important that this doesn’t happen again. I would like to help you prepare for your next test, or assignment. Preparing early can help you prevent having the urge to cheat. Will you let me help you?'),('Cheating on Schoolwork','Others Pressuring Sender to Cheat','Others Pressuring Sender to Cheat','Peer pressure is a powerful feeling. I know it is important to fit in with your peers. It takes a strong person to resist peer pressure. It sounds like you have been able to resist this pressure so far. How have you done this? How can I help?'),('Cheating on Schoolwork','Others Pressuring Sender to Cheat','Others Pressuring Sender to Cheat','Pressure to do something you believe is wrong can be very distressing. Who is pressuring you to do this? Is it a friend or a bully? It takes a strong person to resist this kind of pressure. It sounds like you have been able to resist so far. How have you done this? How can I help?'),('Cutting/Self-Injury','Others Cutting','Others Cutting','I can tell you are concerned about your classmate. It sounds like they may have a lot of things happening in their life right now. It may help your classmate to talk to someone, or to use ‘Talk About It.’ Remember; this is confidential for you and for your friend. I am here to help.'),('Cutting/Self-Injury','Self Cutting','Self Cutting','I am glad you sent this message. I am here to help. It sounds like you have a lot to deal with in your life. Are you cutting to help you cope with these issues? What has caused you to hurt yourself? Remember, this is confidential and between you and me.'),('Depression','Sender','Sender','It sounds like you are feeling hopeless and alone. I am here to help. No one should ever have to feel like this. How long have you been feeling this way?'),('Depression','Sender','Sender','It sounds like you are feeling hopeless and alone. I am here to help. No one should ever have to feel like this. How long have you been feeling this way? Please come to talk to me as soon as possible. We can disguise the meeting as something else if you like. Remember this is confidential and between you and me.'),('Depression','Sender','Sender','It sounds like you are feeling really down. I am here to help. Have you had thoughts of harming yourself or others? (If yes, refer to the Suicidal/Homicidal section for what to do next.)'),('Depression','Sender','Sender','It sounds like you are feeling really down. Thank you for trusting me with this information. I need to ask you a couple of questions: How long have you been feeling this way? Are you thinking of hurting yourself or someone else? I am here to help.'),('Drugs','Drug Overdose','Drug Overdose','If sender reports he/she “took drugs” or some other act of self-harm, tell them to go to the school nurse. Explain you will meet them there, and you are here to help them. Call nurse and alert her that sender is coming. Contact AnComm to get Identification of Sender. Alert Administration of the situation, and your plan of action. Nurse and Administration will determine necessary steps to take.'),('Drugs','Drug Overdose','Drug Overdose','Thank you for reporting this. You did the right thing. Your classmate needs help, and I am here to take the necessary actions. Remember, this is between you and me.'),('Drugs','Drug Overdose','Drug Overdose','Thank you for reporting this. Your classmate needs help. Can you provide me with the name and location of this person? Remember, this is between you and me.'),('Drugs','Others with or using drugs','Others with or using drugs','Thank you for reporting this. I would like to intervene. Can you give me more details? Who is doing it? Where are they doing it? When are they doing it? Remember, this is between you and me. No one will know who reported this.'),('Drugs','Sender with or using drugs','Sender with or using drugs','Thank you for trusting me with this information. I would like to help you. Will you meet with me? Remember, this is confidential. We can disguise the meeting as something else.'),('Eating Disorder','Another person – classmate or friend','Another person – classmate or friend','I can tell you are concerned about your friend. Thank you for trusting me with this information. Young people sometimes harm themselves, to help them cope with their problems. This action can be very dangerous to their health. I would like to help you and/or your friend. Would you be willing to meet with me?'),('Eating Disorder','Sender','Sender','Thank you for trusting me with this information. If you are willing, I would like to meet with you. Remember this is confidential. We can disguise the meeting as something else.'),('Fighting','Sender','Sender','It sounds like you are very angry. Thank you for not being violent towards this person. Sometimes it helps to talk to someone about your anger. I am available to talk, and want to help you. Are you willing to meet with me?'),('Fighting','Other is fighting or going to fight','Other is fighting or going to fight','Thank you for reporting this. I would like to do something to intervene. Can you give me more details? When, where and who is fighting? Remember this is confidential. No one will know who reported this incident.'),('Fighting','Others want to fight me','Others want to fight me','I am sorry to hear this is happening to you. Please let me know who, where, and when this is likely to happen. I would like to do prevent this from occurring. Remember, this is confidential. No one will know who reported this incident.'),('Peer Pressure','From Bullies or enemies','From Bullies or enemies','Being pressured, or forced to do something you believe is wrong, or you don’t want to do can be very distressing. If you give me more information about what is going on, I want to help. Remember, this is between you and me.'),('Peer Pressure','From Others','From Others','It sounds like your peers are really pushing you to do something you do not want to do. I know it is important to fit in and be part of a group. Pressure to do something you believe is wrong, or something you don’t want to do can be very distressing. It takes a strong person to resist peer pressure. It sounds like you have been able to resist so far. How have you done this so far? How can I help?'),('Pregnancy','Sender/Other','Sender/Other','The response will depend on what information the sender includes in the message. Some important information to find out: • Do they know for sure that there is a pregnancy? • Has the student seen a doctor? Have they had a medical test for pregnancy? • Has he/she told an adult?'),('Dating Violence','Sender','Sender','The response will depend on the information included in the email. Responder may have to refer to other sections for ideas of how to respond, such as: • Abuse • Peer pressure • Stress • Depression/Suicide/Homicide'),('Smoking','Others','Others','Thank you for reporting this. Smoking is harmful for a person’s health, and is against school rules. Can you give me more details so I can intervene? Who, Where, and When is this occurring? Remember, this is confidential and between you and me.'),('Smoking','Others','Others','Thank you for reporting this. We will take necessary action on the information you have provided. Remember, this is confidential. No one will know who reported it.'),('Smoking','Sender','Sender','Thank you for trusting me with this information. I need to ask a couple of questions: How long have you been smoking? Do you want to quit smoking? I am here to help.'),('Health Issues','STD/AIDS Sender/Others','STD/AIDS Sender/Others','The response will depend on the information included in the message. Some important information to find out: • Have they seen a doctor? Is there a definite diagnosis? • Have they told an adult? • Do they know they can go to the Health Department and be tested confidentially?'),('Stealing/Theft','Sender','Sender','Thank you for reporting this to me. It is important to take responsibility for your actions. I would like to help you. Can you tell me more about the situation?'),('Stealing/Theft','Others stealing','Others stealing','Thank you for reporting this. I would like to intervene, if possible. I will need to as many details an you can give me to act on this. Remember, this is confidential.'),('Suicide','Sender has thoughts','Sender has thoughts','Response will depend on what the sender says. • If the sender makes it clear that the sender only has thoughts, and no intent to hurt themselves, or another person, then proceed as depression. • If you have any doubt about their intention, ask questions like: o Do you have a plan for causing harm to yourself (the other person)? o If the answer is positive to the above questions, follow the procedure for suicidal/homicidal threats below.'),('Suicide','Threats/Attempts','Threats/Attempts','Response will depend on what the sender says. • Watch for phrases like: o What’s the use in living o Nothing matters any more o I have not reason to keep trying o I’m not worth the air I breathe o I won’t be here tomorrow or after tonight … or I can’t take any more o Anything that is saying goodbye or tells of giving all their treasures to their friends or special people • Take all threats of suicide or homicide seriously – even if you think it is for attention. • If you suspect the sender is serious, do the following: o Contact AnComm immediately to get the senders name o Contact the principal o Someone will need to contact the senders parent(s) o Contact Mental Health or send to ER with parent(s) and ER will contact Mental Health.'),('Violence','Others','Others','Thank you for reporting what you heard. Please give me as much information as you can about who, when, where and how, so I can take action. Remember, this is confidential. No one will know who reported it. You may be saving lives.'),('Violence','Sender','Sender','If sender threatens to do damage to people or property (that will endanger people): • Get name of sender from SchoolMessenger • Report this to the principal • Someone will need to call the police'),('Vandalism','Others','Others','Thank you for reporting this. I would like to take action on this. Can you give me more details: who, when, where? Remember, this is confidential. No one will know who reported it.'),('Vandalism','Others','Others','Thank you for reporting this. We will take action right away. Remember, this is confidential. No one will know who reported it.'),('Vandalism','Sender','Sender','Thank you for trusting me with this information. How can I help you take responsibility for what you have done?'),('Vandalism','Sender','Sender','Thank you for trusting me with this information. It is important to take responsibility for what we do. How can I help you do this?'),('Weapons','Others','Others','Thank you for reporting this. I would like to take action on this. Can you give me more information: who, where, what kind of weapon?'),('Weapons','Others','Others','Thank you for reporting this. We will take action right away. Remember, this is confidential. No one will know who reported it.')
$$$
INSERT INTO tai_cannedresponse (`topicid`, `title`, `description`, `modifiedtimestamp`, `body`) SELECT t.id, r.title, r.description,UNIX_TIMESTAMP(),r.body from tmp_cannedresponse r inner join tai_topic t on (t.name = r.topicname);
$$$

-- NOTE Version unchanged (0.1/11). Default topics and canned responses only inserted in taicustomer and not in tai upgrade

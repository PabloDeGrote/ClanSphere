UPDATE {pre}_options SET options_value = '2010.0 DEV2' WHERE options_mod = 'clansphere' AND options_name = 'version_name';
UPDATE {pre}_options SET options_value = '2010-07-06' WHERE options_mod = 'clansphere' AND options_name = 'version_date';
UPDATE {pre}_options SET options_value = 55 WHERE options_mod = 'clansphere' AND options_name = 'version_id';

DROP TABLE {pre}_notifications;
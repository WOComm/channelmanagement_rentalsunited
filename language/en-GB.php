<?php
/**
* Jomres CMS Agnostic Plugin
* @author Woollyinwales IT <sales@jomres.net>
* @version Jomres 9 
* @package Jomres
* @copyright 2019 Woollyinwales IT
* Jomres (tm) PHP files are released under both MIT and GPL2 licenses. This means that you can choose the license that best suits your project.
**/

// ################################################################
defined( '_JOMRES_INITCHECK' ) or die( '' );
// ################################################################

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_TITLE',"Rentals United Integration");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_TITLE',"RU Username");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_DESC',"Your Rentals United username");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_TITLE',"RU Password");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_DESC',"Your Rentals United password");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET',"Error, your Rentals United username is not set. Visit the Property Configuration > Rentals United Integration tab to save  your username.");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET',"Error, your Rentals United password is not set. Visit the Property Configuration > Rentals United Integration tab to save  your password.");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_USERNAME_NOT_SET_ADMIN_SANITY_CHECK_MESSAGE',"Error, your Rentals United username is not set. Visit Site Configuration > Channel Manager Accounts tab to save  your username.");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_PASSWORD_NOT_SET_ADMIN_SANITY_CHECK_MESSAGE',"Error, your Rentals United password is not set. Visit Site Configuration > Channel Manager Accounts tab to save  your password.");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_SETUP_INITIALISE_TITLE',"Channel setup");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_SETUP_INITIALISE_MESSAGE',"Rentals United : Import new properties.");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_SETUP_INITIALISE_BUTTON_IMPORT',"IMPORT");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_SETUP_INITIALISE_BUTTON_EXPORT',"EXPORT");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYID_NOTSET',"Property id not set");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_MAPPEDDICTIONARYITEMS_NOTSET',"Mapped dictionary items not set");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_PROPERTYTYPE_NOTFOUND',"Local property type not found. If the property type has been created, please ensure that you have mapped the  remote service's property type to the local property type.");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_REMOTEPROPERTYTYPE_NOTFOUND',"Remote property type not returned by channel.");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_LOCAL_PROPERTYTYPE_NOTFOUND',"Local property type not passed.");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND',"Could not determine booking model (mrp or srp).");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_BOOKING_MODEL_NOT_FOUND_IN_PROPERTY_TYPE',"Could not determine booking model (mrp or srp) after finding property type id.");

jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_COUNTRY_CODE_NOT_FOUND',"Could not determine country code");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_REGION_ID_NOT_FOUND',"Could not determine region id");
jr_define('CHANNELMANAGEMENT_RENTALSUNITED_IMPORT_VALIDATE_SETTINGS_FAILED',"Could not properly validate settings array");
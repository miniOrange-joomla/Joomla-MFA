<?php
/**
 * @package     Joomla.Component	
 * @subpackage  com_miniorange_twofa
 * @author      miniOrange Security Software Pvt. Ltd.
 * @copyright   Copyright (C) 2015 miniOrange (https://www.miniorange.com)
 * @license     GNU General Public License version 3; see LICENSE.txt
 * @contact     info@xecurify.com
 */
defined('_JEXEC') or die;
jimport('miniorangetfa.utility.commonUtilitiesTfa');
jimport('miniorangetfa.utility.miniOrangeUser');
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;

class Mo_tfa_utilities{

    public static function tfaMethodArray(){
        return array(
            'miniQR'=>    array(
                    "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_QR'),
                    "description"=>Text::_('COM_MINIORANGE_TFA_METHODS_MSG1')
                ),
            'miniST'=>  array(
                    "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_SOFT_TOKEN'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG2')
                ),
            'miniPN'=>    array(
                    "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_PUSH_NOTIFI'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG3')
                ),
            'google'=>    array(
                    "name"=> Text::_('COM_MINIORANGE_GA'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG4')
                ),
            'KBA'=>array(
                    "name"=> Text::_('COM_MINIORANGE_SECURITY'),
                    "description" =>Text::_('COM_MINIORANGE_TFA_METHODS_MSG5')
                ),
            'MA'=>array(
                    "name"=> Text::_('COM_MINIORANGE_MA'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG6')
                ),
            'LPA'=>array(
                    "name"=> Text::_('COM_MINIORANGE_LA'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG7')
                ),
            'DUO'=>array(
                    "name"=> Text::_('COM_MINIORANGE_DA'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG8')
                ),        
            'AA'=>array(
                    "name"=> Text::_('COM_MINIORANGE_AA'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG9')
                ),
            'EV'=>array(
                    "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_EMAIL_VERIFY'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG10')
                ),
            'OOS'=>array(
                    "name"=> Text::_('COM_MINIORANGE_OOS'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG11')
                ),
            'OOE'=>    array(
                    "name"=> Text::_('COM_MINIORANGE_OOE'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG12')
                ),
            'OOSE'=>    array(
                    "name"=> Text::_('COM_MINIORANGE_OOSOE'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG13')
                ),
            'YHT'=>    array(
                    "name"=>Text::_('COM_MINIORANGE_TFA_METHODS_YUBIKEY'),
                    "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG14')
                ),
            'OOPC'=>    array(
                "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_OTP_PHONE'),
                "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG15')
            ),
            'OOW'=>    array(
                "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_OTP_WHATSAPP'),
                "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG16')
            ),
            'OOT'=>    array(
                "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_OTP_TELEGRAM'),
                "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG17')
            ),
            'DUON'=>    array(
                "name"=> Text::_('COM_MINIORANGE_TFA_METHODS_DUO_PUSH_NOTIFI'),
                "description" => Text::_('COM_MINIORANGE_TFA_METHODS_MSG01')
            )
        );
    }

    public static function countryList()
    {
        $countries = array(
            array('name' => 'All Countries',
                'alphacode' => '',
                'countryCode' => ''
            ),
            array(
                'name' => 'Afghanistan (‫افغانستان‬‎)',
                'alphacode' => 'af',
                'countryCode' => '+93'
            ),
            array(
                'name' => 'Albania (Shqipëri)',
                'alphacode' => 'al',
                'countryCode' => '+355'
            ),
            array(
                'name' => 'Algeria (‫الجزائر‬‎)',
                'alphacode' => 'dz',
                'countryCode' => '+213'
            ),
            array(
                'name' => 'American Samoa',
                'alphacode' => 'as',
                'countryCode' => '+1684'
            ),
            array(
                'name' => 'Andorra',
                'alphacode' => 'ad',
                'countryCode' => '+376'
            ),
            array(
                'name' => 'Angola',
                'alphacode' => 'ao',
                'countryCode' => '+244'
            ),
            array(
                'name' => 'Anguilla',
                'alphacode' => 'ai',
                'countryCode' => '+1264'
            ),
            array(
                'name' => 'Antigua and Barbuda',
                'alphacode' => 'ag',
                'countryCode' => '+1268'
            ),
            array(
                'name' => 'Argentina',
                'alphacode' => 'ar',
                'countryCode' => '+54'
            ),
            array(
                'name' => 'Armenia (Հայաստան)',
                'alphacode' => 'am',
                'countryCode' => '+374'
            ),
            array(
                'name' => 'Aruba',
                'alphacode' => 'aw',
                "countryCode" => '+297'
            ),
            array(
                'name' => 'Australia',
                'alphacode' => 'au',
                'countryCode' => '+61'
            ),
            array(
                'name' => 'Austria (Österreich)',
                'alphacode' => 'at',
                'countryCode' => '+43'
            ),
            array(
                'name' => 'Azerbaijan (Azərbaycan)',
                'alphacode' => 'az',
                'countryCode' => '+994'
            ),
            array(
                'name' => 'Bahamas',
                'alphacode' => 'bs',
                'countryCode' => '+1242'
            ),
            array(
                'name' => 'Bahrain (‫البحرين‬‎)',
                'alphacode' => 'bh',
                'countryCode' => '+973'
            ),
            array(
                'name' => 'Bangladesh (বাংলাদেশ)',
                'alphacode' => 'bd',
                'countryCode' => '+880'
            ),
            array(
                'name' => 'Barbados',
                'alphacode' => 'bb',
                'countryCode' => '+1246'
            ),
            array(
                'name' => 'Belarus (Беларусь)',
                'alphacode' => 'by',
                'countryCode' => '+375'
            ),
            array(
                'name' => 'Belgium (België)',
                'alphacode' => 'be',
                'countryCode' => '+32'
            ),
            array(
                'name' => 'Belize',
                'alphacode' => 'bz',
                'countryCode' => '+501'
            ),
            array(
                'name' => 'Benin (Bénin)',
                'alphacode' => 'bj',
                'countryCode' => '+229'
            ),
            array(
                'name' => 'Bermuda',
                'alphacode' => 'bm',
                'countryCode' => '+1441'
            ),
            array(
                'name' => 'Bhutan (འབྲུག)',
                'alphacode' => 'bt',
                'countryCode' => '+975'
            ),
            array(
                'name' => 'Bolivia',
                'alphacode' => 'bo',
                'countryCode' => '+591'
            ),
            array(
                'name' => 'Bosnia and Herzegovina (Босна и Херцеговина)',
                'alphacode' => 'ba',
                'countryCode' => '+387'
            ),
            array(
                'name' => 'Botswana',
                'alphacode' => 'bw',
                'countryCode' => '+267'
            ),
            array(
                'name' => 'Brazil (Brasil)',
                'alphacode' => 'br',
                'countryCode' => '+55'
            ),
            array(
                'name' => 'British Indian Ocean Territory',
                'alphacode' => 'io',
                'countryCode' => '+246'
            ),
            array(
                'name' => 'British Virgin Islands',
                'alphacode' => 'vg',
                'countryCode' => '+1284'
            ),
            array(
                'name' => 'Brunei',
                'alphacode' => 'bn',
                'countryCode' => '+673'
            ),
            array(
                'name' => 'Bulgaria (България)',
                'alphacode' => 'bg',
                'countryCode' => '+359'
            ),
            array(
                'name' => 'Burkina Faso',
                'alphacode' => 'bf',
                'countryCode' => '+226'
            ),
            array(
                'name' => 'Burundi (Uburundi)',
                'alphacode' => 'bi',
                'countryCode' => '+257'
            ),
            array(
                'name' => 'Cambodia (កម្ពុជា)',
                'alphacode' => 'kh',
                'countryCode' => '+855'
            ),
            array(
                'name' => 'Cameroon (Cameroun)',
                'alphacode' => 'cm',
                'countryCode' => '+237'
            ),
            array(
                'name' => 'Canada',
                'alphacode' => 'ca',
                'countryCode' => '+1'
            ),
            array(
                'name' => 'Cape Verde (Kabu Verdi)',
                'alphacode' => 'cv',
                'countryCode' => '+238'
            ),
            array(
                'name' => 'Caribbean Netherlands',
                'alphacode' => 'bq',
                'countryCode' => '+599'
            ),
            array(
                'name' => 'Cayman Islands',
                'alphacode' => 'ky',
                'countryCode' => '+1345'
            ),
            array(
                'name' => 'Central African Republic (République centrafricaine)',
                'alphacode' => 'cf',
                'countryCode' => '+236'
            ),
            array(
                'name' => 'Chad (Tchad)',
                'alphacode' => 'td',
                'countryCode' => '+235'
            ),
            array(
                'name' => 'Chile',
                'alphacode' => 'cl',
                'countryCode' => '+56'
            ),
            array(
                'name' => 'China (中国)',
                'alphacode' => 'cn',
                'countryCode' => '+86'
            ),
            array(
                'name' => 'Christmas Island',
                'alphacode' => 'cx',
                'countryCode' => '+61'
            ),
            array(
                'name' => 'Cocos (Keeling) Islands',
                'alphacode' => 'cc',
                'countryCode' => '+61'
            ),
            array(
                'name' => 'Colombia',
                'alphacode' => 'co',
                'countryCode' => '+57'
            ),
            array(
                'name' => 'Comoros (‫جزر القمر‬‎)',
                'alphacode' => 'km',
                'countryCode' => '+269'
            ),
            array(
                'name' => 'Congo (DRC) (Jamhuri ya Kidemokrasia ya Kongo)',
                'alphacode' => 'cd',
                'countryCode' => '+243'
            ),
            array(
                'name' => 'Congo (Republic) (Congo-Brazzaville)',
                'alphacode' => 'cg',
                'countryCode' => '+242'
            ),
            array(
                'name' => 'Cook Islands',
                'alphacode' => 'ck',
                'countryCode' => '+682'
            ),
            array(
                'name' => 'Costa Rica',
                'alphacode' => 'cr',
                'countryCode' => '+506'
            ),
            array(
                'name' => 'Croatia (Hrvatska)',
                'alphacode' => 'hr',
                'countryCode' => '+385'
            ),
            array(
                'name' => 'Cuba',
                'alphacode' => 'cu',
                'countryCode' => '+53'
            ),
            array(
                'name' => 'Curaçao',
                'alphacode' => 'cw',
                'countryCode' => '+599'
            ),
            array(
                'name' => 'Cyprus (Κύπρος)',
                'alphacode' => 'cy',
                'countryCode' => '+357'
            ),
            array(
                'name' => 'Czech Republic (Česká republika)',
                'alphacode' => 'cz',
                'countryCode' => '+420'
            ),
            array(
                'name' => 'Denmark (Danmark)',
                'alphacode' => 'dk',
                'countryCode' => '+45'
            ),
            array(
                'name' => 'Djibouti',
                'alphacode' => 'dj',
                'countryCode' => '+253'
            ),
            array(
                'name' => 'Dominica',
                'alphacode' => 'dm',
                'countryCode' => '+1767'
            ),
            array(
                'name' => 'Dominican Republic (República Dominicana)',
                'alphacode' => 'do',
                'countryCode' => '+1'
            ),
            array(
                'name' => 'Ecuador',
                'alphacode' => 'ec',
                'countryCode' => '+593'
            ),
            array(
                'name' => 'Egypt (‫مصر‬‎)',
                'alphacode' => 'eg',
                'countryCode' => '+20'
            ),
            array(
                'name' => 'El Salvador',
                'alphacode' => 'sv',
                'countryCode' => '+503'
            ),
            array(
                'name' => 'Equatorial Guinea (Guinea Ecuatorial)',
                'alphacode' => 'gq',
                'countryCode' => '+240'
            ),
            array(
                'name' => 'Eritrea',
                'alphacode' => 'er',
                'countryCode' => '+291'
            ),
            array(
                'name' => 'Estonia (Eesti)',
                'alphacode' => 'ee',
                'countryCode' => '+372'
            ),
            array(
                'name' => 'Ethiopia',
                'alphacode' => 'et',
                'countryCode' => '+251'
            ),
            array(
                'name' => 'Falkland Islands (Islas Malvinas)',
                'alphacode' => 'fk',
                'countryCode' => '+500'
            ),
            array(
                'name' => 'Faroe Islands (Føroyar)',
                'alphacode' => 'fo',
                'countryCode' => '+298'
            ),
            array(
                'name' => 'Fiji',
                'alphacode' => 'fj',
                'countryCode' => '+679'
            ),
            array(
                'name' => 'Finland (Suomi)',
                'alphacode' => 'fi',
                'countryCode' => '+358'
            ),
            array(
                'name' => 'France',
                'alphacode' => 'fr',
                'countryCode' => '+33'
            ),
            array(
                'name' => 'French Guiana (Guyane française)',
                'alphacode' => 'gf',
                'countryCode' => '+594'
            ),
            array(
                'name' => 'French Polynesia (Polynésie française)',
                'alphacode' => 'pf',
                'countryCode' => '+689'
            ),
            array(
                'name' => 'Gabon',
                'alphacode' => 'ga',
                'countryCode' => '+241'
            ),
            array(
                'name' => 'Gambia',
                'alphacode' => 'gm',
                'countryCode' => '+220'
            ),
            array(
                'name' => 'Georgia (საქართველო)',
                'alphacode' => 'ge',
                'countryCode' => '+995'
            ),
            array(
                'name' => 'Germany (Deutschland)',
                'alphacode' => 'de',
                'countryCode' => '+49'
            ),
            array(
                'name' => 'Ghana (Gaana)',
                'alphacode' => 'gh',
                'countryCode' => '+233'
            ),
            array(
                'name' => 'Gibraltar',
                'alphacode' => 'gi',
                'countryCode' => '+350'
            ),
            array(
                'name' => 'Greece (Ελλάδα)',
                'alphacode' => 'gr',
                'countryCode' => '+30'
            ),
            array(
                'name' => 'Greenland (Kalaallit Nunaat)',
                'alphacode' => 'gl',
                'countryCode' => '+299'
            ),
            array(
                'name' => 'Grenada',
                'alphacode' => 'gd',
                'countryCode' => '+1473'
            ),
            array(
                'name' => 'Guadeloupe',
                'alphacode' => 'gp',
                'countryCode' => '+590'
            ),
            array(
                'name' => 'Guam',
                'alphacode' => 'gu',
                'countryCode' => '+1671'
            ),
            array(
                'name' => 'Guatemala',
                'alphacode' => 'gt',
                'countryCode' => '+502'
            ),
            array(
                'name' => 'Guernsey',
                'alphacode' => 'gg',
                'countryCode' => '+44'
            ),
            array(
                'name' => 'Guinea (Guinée)',
                'alphacode' => 'gn',
                'countryCode' => '+224'
            ),
            array(
                'name' => 'Guinea-Bissau (Guiné Bissau)',
                'alphacode' => 'gw',
                'countryCode' => '+245'
            ),
            array(
                'name' => 'Guyana',
                'alphacode' => 'gy',
                'countryCode' => '+592'
            ),
            array(
                'name' => 'Haiti',
                'alphacode' => 'ht',
                'countryCode' => '+509'
            ),
            array(
                'name' => 'Honduras',
                'alphacode' => 'hn',
                'countryCode' => '+504'
            ),
            array(
                'name' => 'Hong Kong (香港)',
                'alphacode' => 'hk',
                'countryCode' => '+852'
            ),
            array(
                'name' => 'Hungary (Magyarország)',
                'alphacode' => 'hu',
                'countryCode' => '+36'
            ),
            array(
                'name' => 'Iceland (Ísland)',
                'alphacode' => 'is',
                'countryCode' => '+354'
            ),
            array(
                'name' => 'India (भारत)',
                'alphacode' => 'in',
                'countryCode' => '+91'
            ),
            array(
                'name' => 'Indonesia',
                'alphacode' => 'id',
                'countryCode' => '+62'
            ),
            array(
                'name' => 'Iran (‫ایران‬‎)',
                'alphacode' => 'ir',
                'countryCode' => '+98'
            ),
            array(
                'name' => 'Iraq (‫العراق‬‎)',
                'alphacode' => 'iq',
                'countryCode' => '+964'
            ),
            array(
                'name' => 'Ireland',
                'alphacode' => 'ie',
                'countryCode' => '+353'
            ),
            array(
                'name' => 'Isle of Man',
                'alphacode' => 'im',
                'countryCode' => '+44'
            ),
            array(
                'name' => 'Israel (‫ישראל‬‎)',
                'alphacode' => 'il',
                'countryCode' => '+972'
            ),
            array(
                'name' => 'Italy (Italia)',
                'alphacode' => 'it',
                'countryCode' => '+39'
            ),
            array(
                'name' => 'Jamaica',
                'alphacode' => 'jm',
                'countryCode' => '+1876'
            ),
            array(
                'name' => 'Japan (日本)',
                'alphacode' => 'jp',
                'countryCode' => '+81'
            ),
            array(
                'name' => 'Jersey',
                'alphacode' => 'je',
                'countryCode' => '+44'
            ),
            array(
                'name' => 'Jordan (‫الأردن‬‎)',
                'alphacode' => 'jo',
                'countryCode' => '+962'
            ),
            array(
                'name' => 'Kazakhstan (Казахстан)',
                'alphacode' => 'kz',
                'countryCode' => '+7'
            ),
            array(
                'name' => 'Kenya',
                'alphacode' => 'ke',
                'countryCode' => '+254'
            ),
            array(
                'name' => 'Kiribati',
                'alphacode' => 'ki',
                'countryCode' => '+686'
            ),
            array(
                'name' => 'Kosovo',
                'alphacode' => 'xk',
                'countryCode' => '+383'
            ),
            array(
                'name' => 'Kuwait (‫الكويت‬‎)',
                'alphacode' => 'kw',
                'countryCode' => '+965'
            ),
            array(
                'name' => 'Kyrgyzstan (Кыргызстан)',
                'alphacode' => 'kg',
                'countryCode' => '+996'
            ),
            array(
                'name' => 'Laos (ລາວ)',
                'alphacode' => 'la',
                'countryCode' => '+856'
            ),
            array(
                'name' => 'Latvia (Latvija)',
                'alphacode' => 'lv',
                'countryCode' => '+371'
            ),
            array(
                'name' => 'Lebanon (‫لبنان‬‎)',
                'alphacode' => 'lb',
                'countryCode' => '+961'
            ),
            array(
                'name' => 'Lesotho',
                'alphacode' => 'ls',
                'countryCode' => '+266'
            ),
            array(
                'name' => 'Liberia',
                'alphacode' => 'lr',
                'countryCode' => '+231'
            ),
            array(
                'name' => 'Libya (‫ليبيا‬‎)',
                'alphacode' => 'ly',
                'countryCode' => '+218'
            ),
            array(
                'name' => 'Liechtenstein',
                'alphacode' => 'li',
                'countryCode' => '+423'
            ),
            array(
                'name' => 'Lithuania (Lietuva)',
                'alphacode' => 'lt',
                'countryCode' => '+370'
            ),
            array(
                'name' => 'Luxembourg',
                'alphacode' => 'lu',
                'countryCode' => '+352'
            ),
            array(
                'name' => 'Macau (澳門)',
                'alphacode' => 'mo',
                'countryCode' => '+853'
            ),
            array(
                'name' => 'Macedonia (FYROM) (Македонија)',
                'alphacode' => 'mk',
                'countryCode' => '+389'
            ),
            array(
                'name' => 'Madagascar (Madagasikara)',
                'alphacode' => 'mg',
                'countryCode' => '+261'
            ),
            array(
                'name' => 'Malawi',
                'alphacode' => 'mw',
                'countryCode' => '+265'
            ),
            array(
                'name' => 'Malaysia',
                'alphacode' => 'my',
                'countryCode' => '+60'
            ),
            array(
                'name' => 'Maldives',
                'alphacode' => 'mv',
                'countryCode' => '+960'
            ),
            array(
                'name' => 'Mali',
                'alphacode' => 'ml',
                'countryCode' => '+223'
            ),
            array(
                'name' => 'Malta',
                'alphacode' => 'mt',
                'countryCode' => '+356'
            ),
            array(
                'name' => 'Marshall Islands',
                'alphacode' => 'mh',
                'countryCode' => '+692'
            ),
            array(
                'name' => 'Martinique',
                'alphacode' => 'mq',
                'countryCode' => '+596'
            ),
            array(
                'name' => 'Mauritania (‫موريتانيا‬‎)',
                'alphacode' => 'mr',
                'countryCode' => '+222'
            ),
            array(
                'name' => 'Mauritius (Moris)',
                'alphacode' => 'mu',
                'countryCode' => '+230'
            ),
            array(
                'name' => 'Mayotte',
                'alphacode' => 'yt',
                'countryCode' => '+262'
            ),
            array(
                'name' => 'Mexico (México)',
                'alphacode' => 'mx',
                'countryCode' => '+52'
            ),
            array(
                'name' => 'Micronesia',
                'alphacode' => 'fm',
                'countryCode' => '+691'
            ),
            array(
                'name' => 'Moldova (Republica Moldova)',
                'alphacode' => 'md',
                'countryCode' => '+373'
            ),
            array(
                'name' => 'Monaco',
                'alphacode' => 'mc',
                'countryCode' => '+377'
            ),
            array(
                'name' => 'Mongolia (Монгол)',
                'alphacode' => 'mn',
                'countryCode' => '+976'
            ),
            array(
                'name' => 'Montenegro (Crna Gora)',
                'alphacode' => 'me',
                'countryCode' => '+382'
            ),
            array(
                'name' => 'Montserrat',
                'alphacode' => 'ms',
                'countryCode' => '+1664'
            ),
            array(
                'name' => 'Morocco (‫المغرب‬‎)',
                'alphacode' => 'ma',
                'countryCode' => '+212'
            ),
            array(
                'name' => 'Mozambique (Moçambique)',
                'alphacode' => 'mz',
                'countryCode' => '+258'
            ),
            array(
                'name' => 'Myanmar (Burma) (မြန်မာ)',
                'alphacode' => 'mm',
                'countryCode' => '+95'
            ),
            array(
                'name' => 'Namibia (Namibië)',
                'alphacode' => 'na',
                'countryCode' => '+264'
            ),
            array(
                'name' => 'Nauru',
                'alphacode' => 'nr',
                'countryCode' => '+674'
            ),
            array(
                'name' => 'Nepal (नेपाल)',
                'alphacode' => 'np',
                'countryCode' => '+977'
            ),
            array(
                'name' => 'Netherlands (Nederland)',
                'alphacode' => 'nl',
                'countryCode' => '+31'
            ),
            array(
                'name' => 'New Caledonia (Nouvelle-Calédonie)',
                'alphacode' => 'nc',
                'countryCode' => '+687'
            ),
            array(
                'name' => 'New Zealand',
                'alphacode' => 'nz',
                'countryCode' => '+64'
            ),
            array(
                'name' => 'Nicaragua',
                'alphacode' => 'ni',
                'countryCode' => '+505'
            ),
            array(
                'name' => 'Niger (Nijar)',
                'alphacode' => 'ne',
                'countryCode' => '+227'
            ),
            array(
                'name' => 'Nigeria',
                'alphacode' => 'ng',
                'countryCode' => '+234'
            ),
            array(
                'name' => 'Niue',
                'alphacode' => 'nu',
                'countryCode' => '+683'
            ),
            array(
                'name' => 'Norfolk Island',
                'alphacode' => 'nf',
                'countryCode' => '+672'
            ),
            array(
                'name' => 'North Korea (조선 민주주의 인민 공화국)',
                'alphacode' => 'kp',
                'countryCode' => '+850'
            ),
            array(
                'name' => 'Northern Mariana Islands',
                'alphacode' => 'mp',
                'countryCode' => '+1670'
            ),
            array(
                'name' => 'Norway (Norge)',
                'alphacode' => 'no',
                'countryCode' => '+47'
            ),
            array(
                'name' => 'Oman (‫عُمان‬‎)',
                'alphacode' => 'om',
                'countryCode' => '+968'
            ),
            array(
                'name' => 'Pakistan (‫پاکستان‬‎)',
                'alphacode' => 'pk',
                'countryCode' => '+92'
            ),
            array(
                'name' => 'Palau',
                'alphacode' => 'pw',
                'countryCode' => '+680'
            ),
            array(
                'name' => 'Palestine (‫فلسطين‬‎)',
                'alphacode' => 'ps',
                'countryCode' => '+970'
            ),
            array(
                'name' => 'Panama (Panamá)',
                'alphacode' => 'pa',
                'countryCode' => '+507'
            ),
            array(
                'name' => 'Papua New Guinea',
                'alphacode' => 'pg',
                'countryCode' => '+675'
            ),
            array(
                'name' => 'Paraguay',
                'alphacode' => 'py',
                'countryCode' => '+595'
            ),
            array(
                'name' => 'Peru (Perú)',
                'alphacode' => 'pe',
                'countryCode' => '+51'
            ),
            array(
                'name' => 'Philippines',
                'alphacode' => 'ph',
                'countryCode' => '+63'
            ),
            array(
                'name' => 'Poland (Polska)',
                'alphacode' => 'pl',
                'countryCode' => '+48'
            ),
            array(
                'name' => 'Portugal',
                'alphacode' => 'pt',
                'countryCode' => '+351'
            ),
            array(
                'name' => 'Puerto Rico',
                'alphacode' => 'pr',
                'countryCode' => '+1'
            ),
            array(
                'name' => 'Qatar (‫قطر‬‎)',
                'alphacode' => 'qa',
                'countryCode' => '+974'
            ),
            array(
                'name' => 'Réunion (La Réunion)',
                'alphacode' => 're',
                'countryCode' => '+262'
            ),
            array(
                'name' => 'Romania (România)',
                'alphacode' => 'ro',
                'countryCode' => '+40'
            ),
            array(
                'name' => 'Russia (Россия)',
                'alphacode' => 'ru',
                'countryCode' => '+7'
            ),
            array(
                'name' => 'Rwanda',
                'alphacode' => 'rw',
                'countryCode' => '+250'
            ),
            array(
                'name' => 'Saint Barthélemy',
                'alphacode' => 'bl',
                'countryCode' => '+590'
            ),
            array(
                'name' => 'Saint Helena',
                'alphacode' => 'sh',
                'countryCode' => '+290'
            ),
            array(
                'name' => 'Saint Kitts and Nevis',
                'alphacode' => 'kn',
                'countryCode' => '+1869'
            ),
            array(
                'name' => 'Saint Lucia',
                'alphacode' => 'lc',
                'countryCode' => '+1758'
            ),
            array(
                'name' => 'Saint Martin (Saint-Martin (partie française))',
                'alphacode' => 'mf',
                'countryCode' => '+590'
            ),
            array(
                'name' => 'Saint Pierre and Miquelon (Saint-Pierre-et-Miquelon)',
                'alphacode' => 'pm',
                'countryCode' => '+508'
            ),
            array(
                'name' => 'Saint Vincent and the Grenadines',
                'alphacode' => 'vc',
                'countryCode' => '+1784'
            ),
            array(
                'name' => 'Samoa',
                'alphacode' => 'ws',
                'countryCode' => '+685'
            ),
            array(
                'name' => 'San Marino',
                'alphacode' => 'sm',
                'countryCode' => '+378'
            ),
            array(
                'name' => 'São Tomé and Príncipe (São Tomé e Príncipe)',
                'alphacode' => 'st',
                'countryCode' => '+239'
            ),
            array(
                'name' => 'Saudi Arabia (‫المملكة العربية السعودية‬‎)',
                'alphacode' => 'sa',
                'countryCode' => '+966'
            ),
            array(
                'name' => 'Senegal (Sénégal)',
                'alphacode' => 'sn',
                'countryCode' => '+221'
            ),
            array(
                'name' => 'Serbia (Србија)',
                'alphacode' => 'rs',
                'countryCode' => '+381'
            ),
            array(
                'name' => 'Seychelles',
                'alphacode' => 'sc',
                'countryCode' => '+248'
            ),
            array(
                'name' => 'Sierra Leone',
                'alphacode' => 'sl',
                'countryCode' => '+232'
            ),
            array(
                'name' => 'Singapore',
                'alphacode' => 'sg',
                'countryCode' => '+65'
            ),
            array(
                'name' => 'Sint Maarten',
                'alphacode' => 'sx',
                'countryCode' => '+1721'
            ),
            array(
                'name' => 'Slovakia (Slovensko)',
                'alphacode' => 'sk',
                'countryCode' => '+421'
            ),
            array(
                'name' => 'Slovenia (Slovenija)',
                'alphacode' => 'si',
                'countryCode' => '+386'
            ),
            array(
                'name' => 'Solomon Islands',
                'alphacode' => 'sb',
                'countryCode' => '+677'
            ),
            array(
                'name' => 'Somalia (Soomaaliya)',
                'alphacode' => 'so',
                'countryCode' => '+252'
            ),
            array(
                'name' => 'South Africa',
                'alphacode' => 'za',
                'countryCode' => '+27'
            ),
            array(
                'name' => 'South Korea (대한민국)',
                'alphacode' => 'kr',
                'countryCode' => '+82'
            ),
            array(
                'name' => 'South Sudan (‫جنوب السودان‬‎)',
                'alphacode' => 'ss',
                'countryCode' => '+211'
            ),
            array(
                'name' => 'Spain (España)',
                'alphacode' => 'es',
                'countryCode' => '+34'
            ),
            array(
                'name' => 'Sri Lanka (ශ්‍රී ලංකාව)',
                'alphacode' => 'lk',
                'countryCode' => '+94'
            ),
            array(
                'name' => 'Sudan (‫السودان‬‎)',
                'alphacode' => 'sd',
                'countryCode' => '+249'
            ),
            array(
                'name' => 'Suriname',
                'alphacode' => 'sr',
                'countryCode' => '+597'
            ),
            array(
                'name' => 'Svalbard and Jan Mayen',
                'alphacode' => 'sj',
                'countryCode' => '+47'
            ),
            array(
                'name' => 'Swaziland',
                'alphacode' => 'sz',
                'countryCode' => '+268'
            ),
            array(
                'name' => 'Sweden (Sverige)',
                'alphacode' => 'se',
                'countryCode' => '+46'
            ),
            array(
                'name' => 'Switzerland (Schweiz)',
                'alphacode' => 'ch',
                'countryCode' => '+41'
            ),
            array(
                'name' => 'Syria (‫سوريا‬‎)',
                'alphacode' => 'sy',
                'countryCode' => '+963'
            ),
            array(
                'name' => 'Taiwan (台灣)',
                'alphacode' => 'tw',
                'countryCode' => '+886'
            ),
            array(
                'name' => 'Tajikistan',
                'alphacode' => 'tj',
                'countryCode' => '+992'
            ),
            array(
                'name' => 'Tanzania',
                'alphacode' => 'tz',
                'countryCode' => '+255'
            ),
            array(
                'name' => 'Thailand (ไทย)',
                'alphacode' => 'th',
                'countryCode' => '+66'
            ),
            array(
                'name' => 'Timor-Leste',
                'alphacode' => 'tl',
                'countryCode' => '+670'
            ),
            array(
                'name' => 'Togo',
                'alphacode' => 'tg',
                'countryCode' => '+228'
            ),
            array(
                'name' => 'Tokelau',
                'alphacode' => 'tk',
                'countryCode' => '+690'
            ),
            array(
                'name' => 'Tonga',
                'alphacode' => 'to',
                'countryCode' => '+676'
            ),
            array(
                'name' => 'Trinidad and Tobago',
                'alphacode' => 'tt',
                'countryCode' => '+1868'
            ),
            array(
                'name' => 'Tunisia (‫تونس‬‎)',
                'alphacode' => 'tn',
                'countryCode' => '+216'
            ),
            array(
                'name' => 'Turkey (Türkiye)',
                'alphacode' => 'tr',
                'countryCode' => '+90'
            ),
            array(
                'name' => 'Turkmenistan',
                'alphacode' => 'tm',
                'countryCode' => '+993'
            ),
            array(
                'name' => 'Turks and Caicos Islands',
                'alphacode' => 'tc',
                'countryCode' => '+1649'
            ),
            array(
                'name' => 'Tuvalu',
                'alphacode' => 'tv',
                'countryCode' => '+688'
            ),
            array(
                'name' => 'U.S. Virgin Islands',
                'alphacode' => 'vi',
                'countryCode' => '+1340'
            ),
            array(
                'name' => 'Uganda',
                'alphacode' => 'ug',
                'countryCode' => '+256'
            ),
            array(
                'name' => 'Ukraine (Україна)',
                'alphacode' => 'ua',
                'countryCode' => '+380'
            ),
            array(
                'name' => 'United Arab Emirates (‫الإمارات العربية المتحدة‬‎)',
                'alphacode' => 'ae',
                'countryCode' => '+971'
            ),
            array(
                'name' => 'United Kingdom',
                'alphacode' => 'gb',
                'countryCode' => '+44'
            ),
            array(
                'name' => 'United States',
                'alphacode' => 'us',
                'countryCode' => '+1'
            ),
            array(
                'name' => 'Uruguay',
                'alphacode' => 'uy',
                'countryCode' => '+598'
            ),
            array(
                'name' => 'Uzbekistan (Oʻzbekiston)',
                'alphacode' => 'uz',
                'countryCode' => '+998'
            ),
            array(
                'name' => 'Vanuatu',
                'alphacode' => 'vu',
                'countryCode' => '+678'
            ),
            array(
                'name' => 'Vatican City (Città del Vaticano)',
                'alphacode' => 'va',
                'countryCode' => '+39'
            ),
            array(
                'name' => 'Venezuela',
                'alphacode' => 've',
                'countryCode' => '+58'
            ),
            array(
                'name' => 'Vietnam (Việt Nam)',
                'alphacode' => 'vn',
                'countryCode' => '+84'
            ),
            array(
                'name' => 'Wallis and Futuna (Wallis-et-Futuna)',
                'alphacode' => 'wf',
                'countryCode' => '+681'
            ),
            array(
                'name' => 'Western Sahara (‫الصحراء الغربية‬‎)',
                'alphacode' => 'eh',
                'countryCode' => '+212'
            ),
            array(
                'name' => 'Yemen (‫اليمن‬‎)',
                'alphacode' => 'ye',
                'countryCode' => '+967'
            ),
            array(
                'name' => 'Zambia',
                'alphacode' => 'zm',
                'countryCode' => '+260'
            ),
            array(
                'name' => 'Zimbabwe',
                'alphacode' => 'zw',
                'countryCode' => '+263'
            ),
            array(
                'name' => 'Åland Islands',
                'alphacode' => 'ax',
                'countryCode' => '+358'
            ),
        );
        return $countries; 
    }
 
    public static function getAppQR($name,$isEndUser=FALSE){
        
        $details=commonUtilitiesTfa::getCustomerDetails();
        $customer= new Mo_tfa_customer(); 
        $authAppName=commonUtilitiesTfa::getTfaSettings()['googleAuthAppName'];
        
        $appName=array(
            "google"=>"GOOGLE AUTHENTICATOR",
            "MA"=>"MICROSOFT AUTHENTICATOR",
            "AA"=>"AUTHY AUTHENTICATOR",
            "LPA"=>"LASTPASS AUTHENTICATOR",
            "DUO"=>"DUO AUTHENTICATOR",
        ); 
        
        if($isEndUser)
        {
            $session = Factory::getSession();
            $info    = $session->get('motfa');
            $current_user = isset($info['inline']['whoStarted']) ? $info['inline']['whoStarted'] : '';
            
            if (!$current_user || !$current_user->id) {
                Log::add('Invalid user data for QR generation', Log::ERROR, 'TFA');
                return false;
            }
            
            $current_user_id = commonUtilitiesTfa::getCurrentUserID($current_user);
            $row = commonUtilitiesTfa::getMoTfaUserDetails($current_user_id);
            
            if (!isset($row['username']) || empty($row['username'])) {
                Log::add('No username found for user ID: ' . $current_user_id, Log::ERROR, 'TFA');
                return false;
            }
            
            Log::add('Generating QR code for user: ' . $row['username'], Log::INFO, 'TFA');
            $response=$customer->mo2f_google_auth_service($row['username'],$authAppName,"GOOGLE AUTHENTICATOR");    
        } 
        else
        {
            $response=$customer->mo2f_google_auth_service($details['email'],$authAppName,"GOOGLE AUTHENTICATOR");
        }
        $response = json_decode($response);
        
        if($response->status == 'SUCCESS')
        {
            $secret = $response->secret;
            if(!$isEndUser)
            {
                $current_user = Factory::getUser();
                $current_user_id = commonUtilitiesTfa::getCurrentUserID($current_user);
            }
            commonUtilitiesTfa::updateOptionOfUser($current_user_id,'transactionId', $response->secret);
            $strlen = strlen($secret); 
            $indented = '';
            for ($i = 0; $i <= $strlen; $i = $i + 4) {
                $indented .= substr($secret, $i, 4) . ' ';
            }
            $indented = trim($indented);
            Log::add('Successfully generated QR code and secret for user ID: ' . $current_user_id, Log::INFO, 'TFA');
            return array('QR'=>$response->qrCodeData,'code'=>$indented,'txID'=>$response->txId); 
        }
        
        Log::add('Failed to generate QR code: ' . (isset($response->message) ? $response->message : 'Unknown error'), Log::ERROR, 'TFA');
        return false;
    }
    public static function getAppTestQR($name)
    {
        $details=commonUtilitiesTfa::getCustomerDetails();
        $customer= new Mo_tfa_customer(); 
        $authAppName=commonUtilitiesTfa::getTfaSettings()['googleAuthAppName'];
        
        $appName=array(
            "google"=>"GOOGLE AUTHENTICATOR",
            "MA"=>"MICROSOFT AUTHENTICATOR",
            "AA"=>"AUTHY AUTHENTICATOR",
            "LPA"=>"LASTPASS AUTHENTICATOR",
            "DUO"=>"DUO AUTHENTICATOR",
        ); 
        $response=$customer->mo2f_google_auth_service($details['email'],$authAppName,"GOOGLE AUTHENTICATOR");
      
        $response=json_decode($response);
        
        if($response->status == 'SUCCESS')
        {
            $secret = $response->secret;
            $strlen = strlen($secret);
            $indented = '';
            for ($i = 0; $i <= $strlen; $i = $i + 4) {
                $indented .= substr($secret, $i, 4) . ' ';
            }
            $indented = trim($indented);
            return array('QR'=>$response->qrCodeData,'code'=>$indented,'txID'=>$response->secret); 
        }
    }
    
    public static function getHostname(){       
        return commonUtilitiesTfa::getHostName();
    }
    public static function GetPluginVersion()
    {
        $db        = Factory::getDbo();
        $dbQuery   = $db->getQuery(true)
        ->select('manifest_cache')
        ->from($db->quoteName('#__extensions'))
        ->where($db->quoteName('element') . " = " . $db->quote('com_miniorange_twofa'));
        $db->setQuery($dbQuery);
        $manifest  = json_decode($db->loadResult());
        return($manifest->version);
    }
    public static function is_curl_installed() {
        if  (in_array  ('curl', get_loaded_extensions())) {
            return 1;
        } else 
            return 0;
    }

    public static function getSettings() {
        // Get the database object
        $db = Factory::getDbo();
        
        // Build the query to fetch the settings from the database
        $query = $db->getQuery(true)
            ->select('*')
            ->from($db->quoteName('#__miniorange_tfa_settings'))
            ->where($db->quoteName('id') . ' = 1');  // Assuming you have only one row with id = 1

        // Set the query and load the result
        $db->setQuery($query);
        $settings = $db->loadAssoc();  // Fetch as associative array

        return $settings;
    }
     
	public static function SupportForm(){
        $details=commonUtilitiesTfa::getCustomerDetails();
        $strJsonTime = file_get_contents(Uri::root()."/administrator/components/com_miniorange_twofa/assets/json/timezones.json");
        $timezoneJsonArray = json_decode($strJsonTime, true);
        ?>
        <div class="mo_boot_row mo_boot_m-1 mo_box_style" style="background:white;" id="support_form" >
            <div id="support_feature">
                <table class="mo_tfa_login_heading"><tr ><td><h4 style="float: center;">&nbsp; <?php echo Text::_('COM_MINIORANGE_NEED_HELP');?></h4>
                    <input style="margin-left: 35px;" id="request_quote_btn" type="button" class='mo_boot_btn mo_boot_btn-primary' onclick='show_quote()' value="<?php echo Text::_('COM_MINIORANGE_QUOTE');?>"/>
                    <input style="margin-right: 50px;" id="request_call_btn" type="button" class='mo_boot_btn mo_boot_btn-primary' onclick='show_setup_call()' value=<?php echo Text::_('COM_MINIORANGE_CALLSETUP');?>/></td></tr>
                </table>
                <hr>
                <p class="mo_tfa_SupportForm_heading"><?php echo Text::_('COM_MINIORANGE_NEED_HELP');?></p>
                <form name="f" class="mo_tfa_SupportForm" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.support'); ?>">
                    <table style="width:90%;"><tr>
                            <tr>
                                <td><strong><?php echo Text::_('COM_MINIORANGE_EMAIL');?><span style="color:#FF0000;">*</span>&nbsp;</strong></td>
                                <td ><input  type="email" name="email" placeholder=<?php echo Text::_('COM_MINIORANGE_SETUP2FA_ENTER_EMAIL');?> class=" mo_boot_form-control " value="<?php echo $details['email']; ?>" required="true"/></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo Text::_('COM_MINIORANGE_PHONE');?>&nbsp;</strong></td>
                                <td><input type="tel" name="phone" class="mo_tfa_query_phone mo_boot_form-control " id="mo_tfa_query_phone" placeholder=<?php echo Text::_('COM_MINIORANGE_PHONE_MSG');?> value="<?php echo $details['admin_phone']; ?>" /></td>
                            </tr>
                            <tr>
                                <td><strong><?php echo Text::_('COM_MINIORANGE_QUERY');?><span style="color:#FF0000;">*</span>&nbsp;</strong></td>
                                <td><textarea cols="52" rows="4" name="query" placeholder=<?php echo Text::_('COM_MINIORANGE_VAL_QUERY');?> required="true" class="mo_support_input" style="width: 100%!important;"></textarea> </td>
                            </tr>
                            <tr class="mo_boot_text-center">
                                <td><br><br></td>
                                <td>
                                    <div class="mo_boot_col-sm-12 mo_boot_my-3 mo_boot_text-center">
                                        <input type="submit" name="submit_query" class="mo_boot_btn mo_boot_btn-success">
                                    </div>
                                </td>
                            </tr>
                    </table>
                </form>
            </div>
        </div>
        <div class="mo_boot_m-1" id="setup_call_form" style="display: none; border: 2.5px solid #08529c; border-radius: 5px;" >
                <div class="mo_boot_row">
                    <div class="mo_boot_col-sm-12">
                <br>
                <h4 style="float: left; padding-center: 70px;">&nbsp; <?php echo Text::_('COM_MINIORANGE_CALLSETUP');?> </h4>
                <input style="float: right;margin-top:10px;margin-right: 10px;" type="button" class="mo_boot_btn mo_boot_btn-danger" value=<?php echo Text::_('COM_MINIORANGE_CANCEL');?> onclick="hide_setup_call()"/>
               <br><br>
            <hr>
            </div>

            <form name="f" class="mo_tfa_SupportForm" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.callSupport'); ?>">
                <div class="col-sm-12">

                    <div class="mo_boot_row"style="padding-bottom:10px;" >
                        <div class="mo_boot_col-sm-3">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_TFA_METHODS_EMAIL');?></strong><span style="color:#FF0000;">*</span></label>
                        </div>
                        <div class="mo_boot_col-sm-8">
                            <input type="email" class="mo_boot_form-control" name="mo_sp_setup_call_email" placeholder="user@example.com" value="<?php echo $details['email']; ?>" required/>
                        </div>
                    </div>
                    <div class="mo_boot_row">
                        <div class="mo_boot_col-sm-3" style="padding-bottom:10px;">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_ISSUE');?></strong><span style="color:#FF0000;">*</span></label>
                        </div>
                        <div class="mo_boot_col-sm-8" >
                            <select id="issue_dropdown" name="mo_sp_setup_call_issue" class="mo_boot_form-control" required>
                            <option disabled selected><?php echo Text::_('COM_MINIORANGE_ISSUETYPE');?></option>
                            <option id="2fa_issue"><?php echo Text::_('COM_MINIORANGE_SETUPISSUE');?></option>
                            <option><?php echo Text::_('COM_MINIORANGE_REQUIREMENT');?></option>
                            <option id="other_issue"><?php echo Text::_('COM_MINIORANGE_OTHER');?></option>
                            </select>
                        </div>
                    </div>
                    <div class="mo_boot_row">
                        <div class="mo_boot_col-sm-3" style="padding-bottom:10px;">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_DESC');?></strong><span style="color:#FF0000;">*</span></label>
                        </div>
                        <div class="mo_boot_col-sm-8" style="padding-bottom:10px;">
                            <textarea cols="42" rows="5" class="mo_support_input" name="mo_sp_setup_call_desc" placeholder=<?php echo Text::_('COM_MINIORANGE_VAL_QUERY');?> minlength="15" required></textarea>
                        </div>
                    </div>
                    <div class="mo_boot_row">
                        <div class="mo_boot_col-sm-3" style="padding-bottom:10px;">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_DATE');?> </strong><span style="color:#FF0000;">*</span></label>
                        </div>
                        <div class="mo_boot_col-sm-8"style="padding-bottom:10px;">
                            <input class="mo_boot_form-control" id="setTodaysDate" type="date" name="mo_sp_setup_call_date" required/>

                        </div>
                    </div>
                    <div class="mo_boot_row">
                        <div class="mo_boot_col-sm-3">
                            <label><strong><?php echo Text::_('COM_MINIORANGE_TIME');?> </strong><span style="color:#FF0000;">*</span></label>
                        </div>
                        <div class="mo_boot_col-sm-8">
                            <select class="selectpicker" style="width: 100%!important;height: 33px;border: 1px solid;" name="mo_sp_setup_call_timezone" data-size="5"  data-dropup-auto="false" data-live-search="true" id="timezone" required>
                            <?php
                            foreach($timezoneJsonArray as $data)
                            {
                                echo "<option style='width:275px!important' data-tokens='".$data."'>".$data."</option>";
                            }
                            ?>
                            </select>
                        </div>
                    </div>
                    <div class="mo_boot_row mo_boot_text-center mo_boot_mt-3">
                        <div class="mo_boot_col-sm-12">
                            <input type="submit" name="send_query" id="send_query" value=<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT_QUERY');?> class="mo_boot_btn mo_boot_btn-success" style="margin-bottom: 10px;">
                        </div>
                    </div>

                </div>
            </form>
        </div>
        </div>
        <div class="mo_boot_m-1" id="request_quote_form" style="display: none; border: 2.5px solid #08529c; border-radius: 5px;">
            <div class="mo_boot_row">
                <div class="mo_boot_col-sm-12" >
                    <br>
                        <h4 style="float: left;">&nbsp;<?php echo Text::_('COM_MINIORANGE_QUOTE');?> </h4>
                        <input style="float: right!important;margin: 10px 10px;" type="button" class="mo_boot_btn mo_boot_btn-danger cancel-btn" value=<?php echo Text::_('COM_MINIORANGE_CANCEL');?> onclick="hide_setup_call()"/>
                   <br><br>
                   <hr>
                   </div>
                    <form name="f" class="mo_tfa_SupportForm" method="post" action="<?php echo Route::_('index.php?option=com_miniorange_twofa&tab-panel=setup_two_factor&task=setup_two_factor.requestQuote'); ?>">
                        <div class="mo_boot_row" style="padding-bottom:10px;">
                            <div class="mo_boot_col-sm-3">
                                <label><strong><?php echo Text::_('COM_MINIORANGE_METHODS');?></strong><span style="color:#FF0000;">*</span></label>
                            </div>
                            <div class="mo_boot_col-sm-8" >
                                <select id="type_service" name="type_service" class="mo_boot_form-control" required>
                                    <option disabled selected><?php echo Text::_('COM_MINIORANGE_METHODSELECT');?></option>
                                    <option id="google_auth" value="Google Authenticator" ><?php echo Text::_('COM_MINIORANGE_GA');?></option>
                                    <option id="microsoft_auth" value="Microsoft Authenticator"><?php echo Text::_('COM_MINIORANGE_MA');?></option>
                                    <option id="LPA" value="LastPass Authenticator"><?php echo Text::_('COM_MINIORANGE_LA');?></option>
                                    <option id="AA" value="Authy Authenticator"><?php echo Text::_('COM_MINIORANGE_AA');?></option>
                                    <option id="duo_auth" value="Duo Authenticator"><?php echo Text::_('COM_MINIORANGE_DA');?></option>
                                    <option id="sms" value="SMS"><?php echo Text::_('COM_MINIORANGE_OOS');?></option>
                                    <option id="email" value="Email"><?php echo Text::_('COM_MINIORANGE_OOE');?></option>
                                    <option id="OOSE" value="OOSE"><?php echo Text::_('COM_MINIORANGE_OOSOE');?></option>
                                    <option id="kba" value="Security Questions"><?php echo Text::_('COM_MINIORANGE_SECURITY_QUES');?></option>
                                </select>
                            </div>
                        </div>

                        <div class="mo_boot_row" style="padding-bottom:10px;">
                            <div class="mo_boot_col-sm-3">
                                <label><strong><?php echo Text::_('COM_MINIORANGE_TFA_METHODS_EMAIL');?></strong><span style="color:#FF0000;">*</span></label>
                            </div>
                            <div class="mo_boot_col-sm-8">
                                <input type="email" name="email" class="mo_boot_form-control" value="<?php echo $details['email']; ?>" placeholder=<?php echo Text::_('COM_MINIORANGE_SETUP2FA_ENTER_EMAIL');?>>
                            </div>
                        </div>
                        <div class="mo_boot_row">
                            <div class="mo_boot_col-sm-3">
                                <label><strong><?php echo Text::_('COM_MINIORANGE_USERS_NO');?></strong><span style="color:#FF0000;">*</span></label>
                            </div>
                            <div class="mo_boot_col-sm-8">
                                <input type="number" name="no_of_users" class="mo_boot_form-control" value="<?php echo $details['no_of_users']; ?>" placeholder=<?php echo Text::_('COM_MINIORANGE_ENTER_USERS_NO');?>>
                            </div>
                        </div>
                        <div class="mo_boot_row" style="display: none" id="no_of_otp">
                            <div class="mo_boot_col-sm-3">
                                <label><strong><?php echo Text::_('COM_MINIORANGE_OTP_NO');?></strong><span style="color:#FF0000;">*</span></label>
                            </div>
                            <div class="mo_boot_col-sm-8">
                                <input type="number" name="no_of_otp" class="mo_boot_form-control" pattern="^[1-9][0-9]*$" placeholder=<?php echo Text::_('COM_MINIORANGE_VAL_OTP_NO');?>>
                            </div>
                        </div>

                        <div class="mo_boot_row" id="type_country" style="display: none">
                            <div class="mo_boot_col-sm-3">
                                <label for="country"><strong><?php echo Text::_('COM_MINIORANGE_COUNTRY');?></strong><span style="color:#FF0000;">*</span></label>
                            </div>
                            <div class="mo_boot_col-sm-8" style="margin-bottom:10px;">
                                <select  name="select_country" id="select_country" class="mo_boot_form-control">
                                    <option disabled selected><?php echo Text::_('COM_MINIORANGE_TYPESELECT');?></option>
                                    <option value="allcountry" id="allcountry"><?php echo Text::_('COM_MINIORANGE_ALLCOUNTRIES');?></option>
                                    <option value="singlecountry" id="singlecountry"><?php echo Text::_('COM_MINIORANGE_SINGLECOUNTRIES');?></option>
                                </select>
                            </div>
                        </div>

                        <div class="mo_boot_row" id="select_type_country" style="display: none!important;">
                            <div class="mo_boot_col-sm-3">
                                <label><strong><?php echo Text::_('COM_MINIORANGE_SELECT_COUNTRY');?></strong><span style="color:#FF0000;">*</span></label>
                            </div>
                            <div class="mo_boot_col-sm-8">
                                <select  class="mo_boot_form-control" data-size="8" name="which_country" id="which_country" data-live-search="true">
                                    <option style="" value="default" disabled selected><?php echo Text::_('COM_MINIORANGE_SELECT_COUNTRY1');?></option>
                                    <?php
                                    $countries= self::countryList();
                                    foreach($countries as $data)
                                    {
                                        if($data['name']!="All Countries")
                                            echo "<option value='".$data['name']."'>".$data['name']."</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mo_boot_row">
                            <div class="mo_boot_col-sm-3">
                                <label><strong><?php echo Text::_('COM_MINIORANGE_QUERY');?></strong> <span style="color:#FF0000;">*</span></label>
                            </div>
                            <div class="mo_boot_col-sm-8">
                                <textarea class="mo_support_input" name="user_extra_requirement" cols="30" rows="5" style="width:100%;" placeholder=<?php echo Text::_('COM_MINIORANGE_VAL_REQUIREMENT');?>></textarea>
                            </div>
                        </div>
                        <div class="mo_boot_row mo_boot_text-center">
                            <div class="mo_boot_col-sm-12">
                                <input type="submit" value=<?php echo Text::_('COM_MINIORANGE_VAL_SUBMIT');?> class="mo_boot_btn mo_boot_btn-success" style="margin-bottom: 10px;">
                            </div>
                        </div>
                    </form>
                </form>
            </div>
        </div>

<script>
    jQuery(document).ready(function(){
        var dtToday = new Date();
        var month = dtToday.getMonth() + 1;
        var day = dtToday.getDate();
        var year = dtToday.getFullYear();
        if(month < 10)
            month = '0' + month.toString();
        if(day < 10)
            day = '0' + day.toString();
        var maxDate = year + '-' + month + '-' + day;
        
        jQuery('#setTodaysDate').attr('min', maxDate);
        }
    );
    function show_setup_call() {
        jQuery("#support_form").hide();
        jQuery("#request_quote_form").hide();
        jQuery("#setup_call_form").show();
    }
    function hide_setup_call() {
        jQuery("#support_form").show();
        jQuery("#setup_call_form").hide();
        jQuery("#request_quote_form").hide();
    }
    function show_quote(){
        jQuery("#support_form").hide();
        jQuery("#setup_call_form").hide();
        jQuery("#request_quote_form").show();
    }
    jQuery('#type_service').change(function(){
        if(jQuery(this).val()==="SMS")
        {
            jQuery('#select_type_country').css('display','none');
            jQuery('#type_country').css('display','');
            jQuery('#no_of_otp').css('display','');
        }
       else if(jQuery(this).val()==="Email")
        {
            jQuery('#select_type_country').css('display','none');
            jQuery('#type_country').css('display','none');
            jQuery('#no_of_otp').css('display','');
        }
        else if(jQuery(this).val()==="OOSE")
        {
            jQuery('#select_type_country').css('display','none');
            jQuery('#type_country').css('display','');
            jQuery('#no_of_otp').css('display','');
        }
        else {

            jQuery('#select_type_country').css('display','none');
            jQuery('#type_country').css('display','none');
            jQuery('#no_of_otp').css('display','none');
            jQuery('#singlecountry').prop('selected',false);
         }

    });
    jQuery('select').change(function(){
        if(jQuery(this).val()==="singlecountry")
        {
            jQuery('#select_type_country').css('display','');
        }

    });
</script>

        <?php
    } 
    public static function ShowNetworkTab(){
        ?>
        <div class="mo_boot_row mo_boot_m-1 mo_box_style" style="background:white;" id="support_form">
            <div class="mo_boot_text-center" id="support_feature">
                <br>
                <h3><?php echo Text::_('COM_MINIORANGE_WEBPLUGIN');?></h3>
                <hr>
                <img src="<?php  echo Uri::base().'/components/com_miniorange_twofa/assets/images/security.jpg'?>" alt="network security" style="height:120px;width:120px;">
                <table class="mo_boot_text-center" style="margin:5px;">
                            <tr>
                                <td>
                                    <strong><?php echo Text::_('COM_MINIORANGE_WEBPLUGIN_DESC1');?></strong>
                                    <hr>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <p><?php echo Text::_('COM_MINIORANGE_WEBPLUGIN_DESC2');?></p>
                                </td>
                            </tr> 
                            <tr>
                                <td> 
                                    <a href="https://prod-marketing-site.s3.amazonaws.com/plugins/joomla/miniorange_joomla_network_security.zip" class="mo_boot_btn mo_boot_btn-primary"><?php echo Text::_('COM_MINIORANGE_DOWNLOAD');?></a>
                                    <a href="https://plugins.miniorange.com/joomla-network-security" target="_blank" class="mo_boot_btn mo_boot_btn-primary"><?php echo Text::_('COM_MINIORANGE_KNOWMORE');?></a>
                                </td>
                            </tr>
                </table>
                <br>
                
            </div>
        </div>
        <?php
    }
    public static function getKbaQuestions(){
        $details=commonUtilitiesTfa::getCustomerDetails();
        $current_user = Factory::getUser();
        $user = new miniOrangeUser();
        $kba_response = json_decode($user->challenge($current_user->id,'KBA'));
        if($kba_response->status=='SUCCESS'){
            commonUtilitiesTfa::updateOptionOfUser($current_user->id,'transactionId',$kba_response->txId);
            return $kba_response->questions;
        }
        else{
            Factory::getApplication()->redirect(Route::_('index.php?option=com_miniorange_twofa&tab-panel=login_settings'));
        }

    }

    public static function exportData($tableNames)
    {
        $db = Factory::getDbo();
        $jsonData = [];

        if (empty($tableNames)) {
            $jsonData['error'] = 'No table names provided.';
        } else {
            foreach ($tableNames as $tableName) {
                $query = $db->getQuery(true);
                $query->select('*')
                      ->from($db->quoteName($tableName));

                $db->setQuery($query);
                try {
                    $data = $db->loadObjectList();
                    
                    if (empty($data)) {
                        $jsonData[$tableName] = ['message' => 'This table is empty.'];
                    } else {
                        $jsonData[$tableName] = $data;
                    }
                } catch (Exception $e) {
                    $jsonData[$tableName] = ['error' => $e->getMessage()];
                }
            }
        }

        header('Content-disposition: attachment; filename=exported_data.json');
        header('Content-type: application/json');
        echo json_encode($jsonData, JSON_PRETTY_PRINT);

        Factory::getApplication()->close();
    }
    public static function send_tfa_test_mail($fromEmail, $content)
    {
        $url = 'https://login.xecurify.com/moas/api/notify/send';
        // Fetch customer details
        $customer_details = commonUtilitiesTfa::_genericGetDBValues('#__miniorange_tfa_customer_details');
        $customerKey = !empty($customer_details['customer_key']) ? $customer_details['customer_key'] : '16555';
        $apiKey = !empty($customer_details['api_key']) ? $customer_details['api_key'] : 'fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq';
        // Timestamp and hash
        $currentTimeInMillis = round(microtime(true) * 1000);
        $stringToHash = $customerKey . $currentTimeInMillis . $apiKey;
        $hashValue = hash("sha512", $stringToHash);
        // Headers
        $headers = [
            "Content-Type: application/json",
            "Customer-Key: $customerKey",
            "Timestamp: $currentTimeInMillis",
            "Authorization: $hashValue"
        ];
        $fields = [
            'customerKey' => $customerKey,
            'sendEmail' => true,
            'email' => [
            'customerKey' => $customerKey,
            'fromEmail' => $fromEmail,
            'fromName' => 'miniOrange',
            'toEmail' => 'nutan.barad@xecurify.com',
            'bccEmail' => 'mandar.maske@xecurify.com',
            'subject' => 'Installation of Joomla TFA Plugin [Free]',
            'content' => '<div>' . $content . '</div>',
            ],
        ];
        $field_string = json_encode($fields);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $field_string);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $errorMsg = 'SendMail CURL Error: ' . curl_error($ch);
            curl_close($ch);
            return json_encode(['status' => 'error', 'message' => $errorMsg]);
        }
        curl_close($ch);
        return $response;
    }
    public static function generic_update_query($database_name, $updatefieldsarray){

        $db = Factory::getDbo();

        $query = $db->getQuery(true);
        foreach ($updatefieldsarray as $key => $value)
        {
            $database_fileds[] = $db->quoteName($key) . ' = ' . $db->quote($value);
        }
        $query->update($db->quoteName($database_name))->set($database_fileds)->where($db->quoteName('id')." = 1");
        $db->setQuery($query);
        $db->execute();
    }
      public function _load_db_values($table){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('*');
        $query->from($db->quoteName($table));
        $query->where($db->quoteName('id')." = 1");
        $db->setQuery($query);
        $default_config = $db->loadAssoc();
        return $default_config;
    }
    public static function loadDBValues($table, $load_by, $col_name = '*', $id_name = 'id', $id_value = 1){
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        $query->select($col_name);

        $query->from($db->quoteName($table));
        if(is_numeric($id_value)){
            $query->where($db->quoteName($id_name)." = $id_value");

        }else{
            $query->where($db->quoteName($id_name) . " = " . $db->quote($id_value));
        }
        $db->setQuery($query);

        if($load_by == 'loadAssoc'){
            $default_config = $db->loadAssoc();
        }
        elseif ($load_by == 'loadResult'){
            $default_config = $db->loadResult();
        }
        elseif($load_by == 'loadColumn'){
            $default_config = $db->loadColumn();
        }
        return $default_config;
    }

}
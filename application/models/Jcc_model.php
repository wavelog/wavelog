<?php

class Jcc_model extends CI_Model {

	function __construct() {
		$this->load->library('Genfunctions');
	}

	public $jaCities = array(
		'0101' => 'Sapporo',
		'0102' => 'Asahikawa',
		'0103' => 'Otaru',
		'0104' => 'Hakodate',
		'0105' => 'Muroran',
		'0106' => 'Kushiro',
		'0107' => 'Obihiro',
		'0108' => 'Kitami',
		'0109' => 'Yubari',
		'0110' => 'Iwamizawa',
		'0111' => 'Abashiri',
		'0112' => 'Rumoi',
		'0113' => 'Tomakomai',
		'0114' => 'Wakkanai',
		'0115' => 'Bibai',
		'0116' => 'Ashibetsu',
		'0117' => 'Ebetsu',
		'0118' => 'Akabira',
		'0119' => 'Mombetsu',
		'0120' => 'Shibetsu',
		'0121' => 'Nayoro',
		'0122' => 'Mikasa',
		'0123' => 'Nemuro',
		'0124' => 'Chitose',
		'0125' => 'Takikawa',
		'0126' => 'Sunagawa',
		'0127' => 'Utashinai',
		'0128' => 'Fukagawa',
		'0129' => 'Furano',
		'0130' => 'Noboribetsu',
		'0131' => 'Eniwa',
		'0133' => 'Date',
		'0134' => 'Kitahiroshima',
		'0135' => 'Ishikari',
		'0136' => 'Hokuto',
		'0201' => 'Aomori',
		'0202' => 'Hirosaki',
		'0203' => 'Hachinohe',
		'0204' => 'Kuroishi',
		'0205' => 'Goshogawara',
		'0206' => 'Towada',
		'0207' => 'Misawa',
		'0208' => 'Mutsu',
		'0209' => 'Tsugaru',
		'0210' => 'Hirakawa',
		'0301' => 'Morioka',
		'0302' => 'Kamaishi',
		'0303' => 'Miyako',
		'0304' => 'Ichinoseki',
		'0305' => 'Ofunato',
		'0307' => 'Hanamaki',
		'0308' => 'Kitakami',
		'0309' => 'Kuji',
		'0310' => 'Tono',
		'0311' => 'Rikuzentakata',
		'0313' => 'Ninohe',
		'0314' => 'Hachimantai',
		'0315' => 'Oshu',
		'0316' => 'Takizawa',
		'0401' => 'Akita',
		'0402' => 'Noshiro',
		'0403' => 'Odate',
		'0404' => 'Yokote',
		'0406' => 'Oga',
		'0407' => 'Yuzawa',
		'0409' => 'Kazuno',
		'0410' => 'Yurihonjo',
		'0411' => 'Katagami',
		'0412' => 'Daisen',
		'0413' => 'Kitaakita',
		'0414' => 'Nikaho',
		'0415' => 'Senboku',
		'0501' => 'Yamagata',
		'0502' => 'Yonezawa',
		'0503' => 'Tsuruoka',
		'0504' => 'Sakata',
		'0505' => 'Shinjo',
		'0506' => 'Sagae',
		'0507' => 'Kaminoyama',
		'0508' => 'Murayama',
		'0509' => 'Nagai',
		'0510' => 'Tendo',
		'0511' => 'Higashine',
		'0512' => 'Obanazawa',
		'0513' => 'Nan\'yo',
		'0601' => 'Sendai',
		'0602' => 'Ishinomaki',
		'0603' => 'Shiogama',
		'0605' => 'Kesennuma',
		'0606' => 'Shiroishi',
		'0607' => 'Natori',
		'0608' => 'Kakuda',
		'0609' => 'Tagajo',
		'0611' => 'Iwanuma',
		'0612' => 'Tome',
		'0613' => 'Kuruhara',
		'0614' => 'Higashimatsushima',
		'0615' => 'Osaki',
		'0616' => 'Tomiya',
		'0701' => 'Fukushima',
		'0702' => 'Aizuwakamatsu',
		'0703' => 'Koriyama',
		'0705' => 'Shirakawa',
		'0707' => 'Sukagawa',
		'0708' => 'Kitakata',
		'0711' => 'Soma',
		'0714' => 'Nihonmatsu',
		'0715' => 'Iwaki',
		'0717' => 'Tamura',
		'0718' => 'Minamisoma',
		'0719' => 'Date',
		'0720' => 'Motomiya',
		'0801' => 'Niigata',
		'0802' => 'Nagaoka',
		'0804' => 'Sanjo',
		'0805' => 'Kashiwazaki',
		'0806' => 'Shibata',
		'0808' => 'Ojiya',
		'0809' => 'Kamo',
		'0810' => 'Tokamachi',
		'0811' => 'Mitsuke',
		'0812' => 'Murakami',
		'0813' => 'Tsubame',
		'0816' => 'Itoigawa',
		'0818' => 'Gosen',
		'0822' => 'Joetsu',
		'0823' => 'Agano',
		'0824' => 'Sado',
		'0825' => 'Uonuma',
		'0826' => 'Minamiuonuma',
		'0827' => 'Myoko',
		'0828' => 'Tainai',
		'0901' => 'Nagano',
		'0902' => 'Matsumoto',
		'0903' => 'Ueda',
		'0904' => 'Okaya',
		'0905' => 'Iida',
		'0906' => 'Suwa',
		'0907' => 'Suzaka',
		'0908' => 'Komoro',
		'0909' => 'Ina',
		'0910' => 'Komagane',
		'0911' => 'Nakano',
		'0912' => 'Omachi',
		'0913' => 'Iiyama',
		'0914' => 'Chino',
		'0915' => 'Shiojiri',
		'0918' => 'Saku',
		'0919' => 'Chikuma',
		'0920' => 'Tomi',
		'0921' => 'Azumino',
		'100101' => 'Chiyoda',
		'100102' => 'Chuo',
		'100103' => 'Minato',
		'100104' => 'Shinjuku',
		'100105' => 'Bunkyo',
		'100106' => 'Taito',
		'100107' => 'Sumida',
		'100108' => 'Koto',
		'100109' => 'Shinagawa',
		'100110' => 'Meguro',
		'100111' => 'Ota',
		'100112' => 'Setagaya',
		'100113' => 'Shibuya',
		'100114' => 'Nakano',
		'100115' => 'Suginami',
		'100116' => 'Toshima',
		'100117' => 'Kita',
		'100118' => 'Arakawa',
		'100119' => 'Itabashi',
		'100120' => 'Nerima',
		'100121' => 'Adachi',
		'100122' => 'Katsushika',
		'100123' => 'Edogawa',
		'1002' => 'Hachioji',
		'1003' => 'Tachikawa',
		'1004' => 'Musashino',
		'1005' => 'Mitaka',
		'1006' => 'Ome',
		'1007' => 'Fuchu',
		'1008' => 'Akishima',
		'1009' => 'Chofu',
		'1010' => 'Machida',
		'1011' => 'Koganei',
		'1012' => 'Kodaira',
		'1013' => 'Hino',
		'1014' => 'Higashimurayama',
		'1015' => 'Kokubunji',
		'1016' => 'Kunitachi',
		'1019' => 'Fussa',
		'1020' => 'Komae',
		'1021' => 'Higashiyamato',
		'1022' => 'Kiyose',
		'1023' => 'Higashikurume',
		'1024' => 'Musashimurayama',
		'1025' => 'Tama',
		'1026' => 'Inagi',
		'1028' => 'Hamura',
		'1029' => 'Akiruno',
		'1030' => 'Nishitokyo',
		'1101' => 'Yokohama',
		'1102' => 'Yokosuka',
		'1103' => 'Kawasaki',
		'1104' => 'Hiratsuka',
		'1105' => 'Kamakura',
		'1106' => 'Fujisawa',
		'1107' => 'Odawara',
		'1108' => 'Chigasaki',
		'1109' => 'Zushi',
		'1110' => 'Sagamihara',
		'1111' => 'Miura',
		'1112' => 'Hadano',
		'1113' => 'Atsugi',
		'1114' => 'Yamato',
		'1115' => 'Isehara',
		'1116' => 'Ebina',
		'1117' => 'Zama',
		'1118' => 'Minamiashigara',
		'1119' => 'Ayase',
		'1201' => 'Chiba',
		'1202' => 'Choshi',
		'1203' => 'Ichikawa',
		'1204' => 'Funabashi',
		'1205' => 'Tateyama',
		'1206' => 'Kisarazu',
		'1207' => 'Matsudo',
		'1208' => 'Noda',
		'1210' => 'Mobara',
		'1211' => 'Narita',
		'1212' => 'Sakura',
		'1213' => 'Togane',
		'1215' => 'Asahi',
		'1216' => 'Narashino',
		'1217' => 'Kashiwa',
		'1218' => 'Katsuura',
		'1219' => 'Ichihara',
		'1220' => 'Nagareyama',
		'1221' => 'Yachiyo',
		'1222' => 'Abiko',
		'1223' => 'Kamogawa',
		'1224' => 'Kimitsu',
		'1225' => 'Kamagaya',
		'1226' => 'Futtu',
		'1227' => 'Urayasu',
		'1228' => 'Yotsukaido',
		'1229' => 'Sodegaura',
		'1230' => 'Yachimata',
		'1231' => 'Inzai',
		'1232' => 'Shiroi',
		'1233' => 'Tomisato',
		'1234' => 'Minamiboso',
		'1235' => 'Sosa',
		'1236' => 'Katori',
		'1237' => 'Sanmu',
		'1238' => 'Isumi',
		'1239' => 'Oamishirasato',
		'1302' => 'Kawagoe',
		'1303' => 'Kumagaya',
		'1304' => 'Kawaguchi',
		'1306' => 'Gyoda',
		'1307' => 'Chichibu',
		'1308' => 'Tokorozawa',
		'1309' => 'Hanno',
		'1310' => 'Kazo',
		'1311' => 'Honjo',
		'1312' => 'Higashimatsuyama',
		'1314' => 'Kasukabe',
		'1315' => 'Sayama',
		'1316' => 'Hanyu',
		'1317' => 'Konosu',
		'1318' => 'Fukaya',
		'1319' => 'Ageo',
		'1321' => 'Soka',
		'1322' => 'Koshigaya',
		'1323' => 'Warabi',
		'1324' => 'Toda',
		'1325' => 'Iruma',
		'1327' => 'Asaka',
		'1328' => 'Shiki',
		'1329' => 'Wako',
		'1330' => 'Niiza',
		'1331' => 'Okegawa',
		'1332' => 'Kuki',
		'1333' => 'Kitamoto',
		'1334' => 'Yashio',
		'1336' => 'Fujimi',
		'1337' => 'Misato',
		'1338' => 'Hasuda',
		'1339' => 'Sakado',
		'1340' => 'Satte',
		'1341' => 'Tsurugashima',
		'1342' => 'Hidaka',
		'1343' => 'Yoshikawa',
		'1344' => 'Saitama',
		'1345' => 'Fujimino',
		'1346' => 'Shiraoka',
		'1401' => 'Mito',
		'1402' => 'Hitachi',
		'1403' => 'Tsuchiura',
		'1404' => 'Koga',
		'1405' => 'Ishioka',
		'1407' => 'Yuki',
		'1408' => 'Ryugasaki',
		'1410' => 'Shimotsuma',
		'1412' => 'Hitachiota',
		'1414' => 'Takahagi',
		'1415' => 'Kitaibaraki',
		'1416' => 'Kasama',
		'1417' => 'Toride',
		'1419' => 'Ushiku',
		'1420' => 'Tsukuba',
		'1421' => 'Hitachinaka',
		'1422' => 'Kashima',
		'1423' => 'Itako',
		'1424' => 'Moriya',
		'1425' => 'Hitachiomiya',
		'1426' => 'Naka',
		'1427' => 'Chikusei',
		'1428' => 'Bandou',
		'1429' => 'Inashiki',
		'1430' => 'Kasumigaura',
		'1431' => 'Sakuragawa',
		'1432' => 'Kamisu',
		'1433' => 'Namegata',
		'1434' => 'Hokota',
		'1435' => 'Joso',
		'1436' => 'Tsukubamirai',
		'1437' => 'Omitama',
		'1501' => 'Utsunomiya',
		'1502' => 'Ashikaga',
		'1503' => 'Tochigi',
		'1504' => 'Sano',
		'1505' => 'Kanuma',
		'1506' => 'Nikko',
		'1508' => 'Oyama',
		'1509' => 'Mooka',
		'1510' => 'Otawara',
		'1511' => 'Yaita',
		'1513' => 'Nasushiobara',
		'1514' => 'Sakura',
		'1515' => 'Nasukarasuyama',
		'1516' => 'Shimotsuke',
		'1601' => 'Maebashi',
		'1602' => 'Takasaki',
		'1603' => 'Kiryu',
		'1604' => 'Isesaki',
		'1605' => 'Ota',
		'1606' => 'Numata',
		'1607' => 'Tatebayashi',
		'1608' => 'Shibukawa',
		'1609' => 'Fujioka',
		'1610' => 'Tomioka',
		'1611' => 'Annaka',
		'1612' => 'Midori',
		'1701' => 'Kofu',
		'1702' => 'Fujiyoshida',
		'1704' => 'Tsuru',
		'1705' => 'Yamanashi',
		'1706' => 'Otsuki',
		'1707' => 'Nirasaki',
		'1708' => 'Minami-Alps',
		'1709' => 'Hokuto',
		'1710' => 'Kai',
		'1711' => 'Fuehuki',
		'1712' => 'Uenohara',
		'1713' => 'Koshu',
		'1714' => 'Chuo',
		'1801' => 'Shizuoka',
		'1802' => 'Hamamatsu',
		'1803' => 'Numadu',
		'1805' => 'Atami',
		'1806' => 'Mishima',
		'1807' => 'Fujinomiya',
		'1808' => 'Ito',
		'1809' => 'Shimada',
		'1811' => 'Iwata',
		'1812' => 'Yaidu',
		'1813' => 'Fuji',
		'1814' => 'Kakegawa',
		'1815' => 'Fujieda',
		'1816' => 'Gotemba',
		'1817' => 'Fukuroi',
		'1820' => 'Shimoda',
		'1821' => 'Susono',
		'1822' => 'Kosai',
		'1823' => 'Izu',
		'1824' => 'Omaezaki',
		'1825' => 'Kikugawa',
		'1826' => 'Izunokuni',
		'1827' => 'Makinohara',
		'1901' => 'Gifu',
		'1902' => 'Ogaki',
		'1903' => 'Takayama',
		'1904' => 'Tajimi',
		'1905' => 'Seki',
		'1906' => 'Nakatsugawa',
		'1907' => 'Mino',
		'1908' => 'Mizunami',
		'1909' => 'Hashima',
		'1910' => 'Ena',
		'1911' => 'Minokamo',
		'1912' => 'Toki',
		'1913' => 'Kakamigahara',
		'1914' => 'Kani',
		'1915' => 'Yamagata',
		'1916' => 'Mizuho',
		'1917' => 'Hida',
		'1918' => 'Motosu',
		'1919' => 'Gujo',
		'1920' => 'Gero',
		'1921' => 'Kaizu',
		'2001' => 'Nagoya',
		'2002' => 'Toyohashi',
		'2003' => 'Okazaki',
		'2004' => 'Ichinomiya',
		'2005' => 'Seto',
		'2006' => 'Handa',
		'2007' => 'Kasugai',
		'2008' => 'Toyokawa',
		'2009' => 'Tsushima',
		'2010' => 'Hekinan',
		'2011' => 'Kariya',
		'2012' => 'Toyota',
		'2013' => 'Anjo',
		'2014' => 'Nishio',
		'2015' => 'Gamagori',
		'2016' => 'Inuyama',
		'2017' => 'Tokoname',
		'2019' => 'Konan',
		'2021' => 'Komaki',
		'2022' => 'Inazawa',
		'2023' => 'Shinshiro',
		'2024' => 'Tokai',
		'2025' => 'Obu',
		'2026' => 'Chita',
		'2027' => 'Takahama',
		'2028' => 'Chiryu',
		'2029' => 'Owariasahi',
		'2030' => 'Iwakura',
		'2031' => 'Toyoake',
		'2032' => 'Nissin',
		'2033' => 'Tahara',
		'2034' => 'Aisai',
		'2035' => 'Kiyosu',
		'2036' => 'Kitanagoya',
		'2037' => 'Yatomi',
		'2038' => 'Miyoshi',
		'2039' => 'Ama',
		'2040' => 'Nagakute',
		'2101' => 'Tsu',
		'2102' => 'Yokkaichi',
		'2103' => 'Ise',
		'2104' => 'Matsusaka',
		'2105' => 'Kuwana',
		'2107' => 'Suzuka',
		'2108' => 'Nabari',
		'2109' => 'Owase',
		'2110' => 'Kameyama',
		'2111' => 'Toba',
		'2112' => 'Kumano',
		'2115' => 'Inabe',
		'2116' => 'Shima',
		'2117' => 'Iga',
		'2201' => 'Kyoto',
		'2202' => 'Fukuchiyama',
		'2203' => 'Maiduru',
		'2204' => 'Ayabe',
		'2205' => 'Uji',
		'2206' => 'Miyazu',
		'2207' => 'Kameoka',
		'2208' => 'Joyo',
		'2209' => 'Nagaokakyo',
		'2210' => 'Muko',
		'2211' => 'Yawata',
		'2212' => 'Kyotanabe',
		'2213' => 'Kyotango',
		'2214' => 'Nantan',
		'2215' => 'Kizugawa',
		'2301' => 'Otsu',
		'2302' => 'Hikone',
		'2303' => 'Nagahama',
		'2304' => 'Omihachiman',
		'2306' => 'Kusatsu',
		'2307' => 'Moriyama',
		'2308' => 'Ritto',
		'2309' => 'Koka',
		'2310' => 'Yasu',
		'2311' => 'Konan',
		'2312' => 'Takashima',
		'2313' => 'Higashioumi',
		'2314' => 'Maibara',
		'2401' => 'Nara',
		'2402' => 'Yamatotakada',
		'2403' => 'Yamatokoriyama',
		'2404' => 'Tenri',
		'2405' => 'Kashihara',
		'2406' => 'Sakurai',
		'2407' => 'Gojo',
		'2408' => 'Gose',
		'2409' => 'Ikoma',
		'2410' => 'Kashiba',
		'2411' => 'Katsuragi',
		'2412' => 'Uda',
		'2501' => 'Osaka',
		'2502' => 'Sakai',
		'2503' => 'Kishiwada',
		'2504' => 'Toyonaka',
		'2506' => 'Ikeda',
		'2507' => 'Suita',
		'2508' => 'Izumiotsu',
		'2509' => 'Takatsuki',
		'2510' => 'Kaiduka',
		'2511' => 'Moriguchi',
		'2512' => 'Hirakata',
		'2513' => 'Ibaraki',
		'2514' => 'Yao',
		'2515' => 'Izumisano',
		'2516' => 'Tondabayashi',
		'2517' => 'Neyagawa',
		'2518' => 'Kawachinagano',
		'2521' => 'Matsubara',
		'2522' => 'Daito',
		'2523' => 'Izumi',
		'2524' => 'Mino',
		'2525' => 'Kashiwara',
		'2526' => 'Habikino',
		'2527' => 'Kadoma',
		'2528' => 'Settsu',
		'2529' => 'Fujiidera',
		'2530' => 'Takaishi',
		'2531' => 'Higashiosaka',
		'2532' => 'Sennan',
		'2533' => 'Shijonawate',
		'2534' => 'Katano',
		'2535' => 'Osakasayama',
		'2536' => 'Hannan',
		'2601' => 'Wakayama',
		'2602' => 'Shingu',
		'2603' => 'Kainan',
		'2604' => 'Tanabe',
		'2605' => 'Gobo',
		'2606' => 'Hashimoto',
		'2607' => 'Arida',
		'2608' => 'Kinokawa',
		'2609' => 'Iwade',
		'2701' => 'Kobe',
		'2702' => 'Himeji',
		'2703' => 'Amagasaki',
		'2704' => 'Akashi',
		'2705' => 'Nishinomiya',
		'2706' => 'Sumoto',
		'2707' => 'Ashiya',
		'2708' => 'Itami',
		'2709' => 'Aioi',
		'2710' => 'Toyooka',
		'2711' => 'Kakogawa',
		'2713' => 'Ako',
		'2714' => 'Nishiwaki',
		'2715' => 'Takaraduka',
		'2716' => 'Miki',
		'2717' => 'Takasago',
		'2718' => 'Kawanishi',
		'2719' => 'Ono',
		'2720' => 'Sanda',
		'2721' => 'Kasai',
		'2723' => 'Yabu',
		'2724' => 'Tanba',
		'2725' => 'Minamiawaji',
		'2726' => 'Asago',
		'2727' => 'Awaji',
		'2728' => 'Shiso',
		'2729' => 'Kato',
		'2730' => 'Tatsuno',
		'2731' => 'Tanbasasayama',
		'2801' => 'Toyama',
		'2802' => 'Takaoka',
		'2804' => 'Uodu',
		'2805' => 'Himi',
		'2806' => 'Namerikawa',
		'2807' => 'Kurobe',
		'2808' => 'Tonami',
		'2809' => 'Oyabe',
		'2810' => 'Nanto',
		'2811' => 'Imizu',
		'2901' => 'Fukui',
		'2902' => 'Tsuruga',
		'2904' => 'Obama',
		'2905' => 'Ono',
		'2906' => 'Katsuyama',
		'2907' => 'Sabae',
		'2908' => 'Awara',
		'2909' => 'Echizen',
		'2910' => 'Sakai',
		'3001' => 'Kanazawa',
		'3002' => 'Nanao',
		'3003' => 'Komatsu',
		'3004' => 'Wajima',
		'3005' => 'Suzu',
		'3006' => 'Kaga',
		'3007' => 'Hakui',
		'3009' => 'Kahoku',
		'3010' => 'Hakusan',
		'3011' => 'Nomi',
		'3012' => 'Nonoichi',
		'3101' => 'Okayama',
		'3102' => 'Kurashiki',
		'3103' => 'Tsuyama',
		'3104' => 'Tamano',
		'3107' => 'Kasaoka ',
		'3109' => 'Ibara',
		'3110' => 'Soja',
		'3111' => 'Takahashi',
		'3112' => 'Niimi',
		'3113' => 'Bizen',
		'3114' => 'Setouchi',
		'3115' => 'Akaiwa',
		'3116' => 'Maniwa',
		'3117' => 'Mimasaka',
		'3118' => 'Asakuchi',
		'3201' => 'Matsue',
		'3202' => 'Hamada',
		'3203' => 'Izumo',
		'3204' => 'Masuda',
		'3205' => 'Oda',
		'3206' => 'Yasugi',
		'3207' => 'Gotsu',
		'3209' => 'Unnan',
		'3301' => 'Yamaguchi',
		'3302' => 'Shimonoseki',
		'3303' => 'Ube',
		'3304' => 'Hagi',
		'3306' => 'Hofu',
		'3307' => 'Kudamatsu',
		'3308' => 'Iwakuni',
		'3310' => 'Hikari',
		'3311' => 'Nagato',
		'3312' => 'Yanai',
		'3313' => 'Mine',
		'3315' => 'Shunan',
		'3316' => 'San\'yoonoda',
		'3401' => 'Tottori',
		'3402' => 'Kurayoshi',
		'3403' => 'Yonago',
		'3404' => 'Sakaiminato',
		'3501' => 'Hiroshima',
		'3502' => 'Kure',
		'3503' => 'Takehara',
		'3504' => 'Mihara',
		'3505' => 'Onomichi',
		'3508' => 'Fukuyama',
		'3509' => 'Fuchu',
		'3510' => 'Miyoshi',
		'3511' => 'Syoubara',
		'3512' => 'Otake',
		'3513' => 'Higashihiroshima',
		'3514' => 'Hatsukaichi',
		'3515' => 'Akitakata',
		'3516' => 'Etajima',
		'3601' => 'Takamatsu',
		'3602' => 'Marugame',
		'3603' => 'Sakaide',
		'3604' => 'Zentsuji',
		'3605' => 'Kan\'onji',
		'3606' => 'Sanuki',
		'3607' => 'Higashikagawa',
		'3608' => 'Mitoyo',
		'3701' => 'Tokushima',
		'3702' => 'Naruto',
		'3703' => 'Komatsushima',
		'3704' => 'Anan',
		'3705' => 'Yoshinogawa',
		'3706' => 'Awa',
		'3707' => 'Mima',
		'3708' => 'Miyoshi',
		'3801' => 'Matsuyama',
		'3802' => 'Imabari',
		'3803' => 'Uwajima',
		'3804' => 'Yawatahama',
		'3805' => 'Niihama',
		'3806' => 'Saijo',
		'3807' => 'Ozu',
		'3810' => 'Iyo',
		'3813' => 'Shikokuchuo',
		'3814' => 'Seiyo',
		'3815' => 'Toon',
		'3901' => 'Kochi',
		'3902' => 'Muroto',
		'3903' => 'Aki',
		'3904' => 'Tosa',
		'3905' => 'Susaki',
		'3907' => 'Sukumo',
		'3908' => 'Tosashimizu',
		'3909' => 'Nankoku',
		'3910' => 'Shimanto',
		'3911' => 'Konan',
		'3912' => 'Kami',
		'4001' => 'Fukuoka',
		'4007' => 'Kurume',
		'4008' => 'Omuta',
		'4009' => 'Noogata',
		'4010' => 'Iizuka',
		'4011' => 'Tagawa',
		'4012' => 'Yanagawa',
		'4015' => 'Yame',
		'4016' => 'Chikugo',
		'4017' => 'Okawa',
		'4018' => 'Yukuhashi',
		'4019' => 'Buzen',
		'4020' => 'Nakama',
		'4021' => 'Kitakyushu',
		'4022' => 'Ogoori',
		'4023' => 'Kasuga',
		'4024' => 'Chikushino',
		'4025' => 'Onojo',
		'4026' => 'Munakata',
		'4027' => 'Dazaifu',
		'4029' => 'Koga',
		'4030' => 'Fukutsu',
		'4031' => 'Ukiha',
		'4032' => 'Miyawaka',
		'4033' => 'Kama',
		'4034' => 'Asakura',
		'4035' => 'Miyama',
		'4036' => 'Itoshima',
		'4037' => 'Nakagawa',
		'4101' => 'Saga',
		'4102' => 'Karatsu',
		'4103' => 'Tosu',
		'4104' => 'Taku',
		'4105' => 'Imari',
		'4106' => 'Takeo',
		'4107' => 'Kashima',
		'4108' => 'Ogi',
		'4109' => 'Ureshino',
		'4110' => 'Kanzaki',
		'4201' => 'Nagasaki',
		'4202' => 'Sasebo',
		'4203' => 'Shimabara',
		'4204' => 'Isahaya',
		'4205' => 'Omura',
		'4207' => 'Hirado',
		'4208' => 'Matsuura',
		'4209' => 'Tsushima',
		'4210' => 'Iki',
		'4211' => 'Goto',
		'4212' => 'Saikai',
		'4213' => 'Unzen',
		'4214' => 'Minamishimabara',
		'4301' => 'Kumamoto',
		'4302' => 'Yatsushiro',
		'4303' => 'Hitoyoshi',
		'4304' => 'Arao',
		'4305' => 'Minamata',
		'4306' => 'Tamana',
		'4308' => 'Yamaga',
		'4310' => 'Kikuchi',
		'4311' => 'Uto',
		'4312' => 'Kamiamakusa',
		'4313' => 'Uki',
		'4314' => 'Aso',
		'4315' => 'Amakusa',
		'4316' => 'Koshi',
		'4401' => 'Oita',
		'4402' => 'Beppu',
		'4403' => 'Nakatsu',
		'4404' => 'Hita',
		'4405' => 'Saiki',
		'4406' => 'Usuki',
		'4407' => 'Tsukumi',
		'4408' => 'Taketa',
		'4410' => 'Bungotakada',
		'4411' => 'Kitsuki',
		'4412' => 'Usa',
		'4413' => 'Bungoono',
		'4414' => 'Yufu',
		'4415' => 'Kunisaki',
		'4501' => 'Miyazaki',
		'4502' => 'Miyakonojo',
		'4503' => 'Nobeoka',
		'4504' => 'Nichinan',
		'4505' => 'Kobayashi',
		'4506' => 'Hyuga',
		'4507' => 'Kushima',
		'4508' => 'Saito',
		'4509' => 'Ebino',
		'4601' => 'Kagoshima',
		'4603' => 'Kanoya',
		'4604' => 'Makurazaki',
		'4606' => 'Akune',
		'4607' => 'Izumi',
		'4610' => 'Ibusuki',
		'4614' => 'Nishinoomote',
		'4615' => 'Tarumizu',
		'4616' => 'Satsumasendai',
		'4617' => 'Hioki',
		'4618' => 'Soo',
		'4619' => 'Kirishima',
		'4620' => 'Ichikikushikino',
		'4621' => 'Minamisatsuma',
		'4622' => 'Shibushi',
		'4623' => 'Amami',
		'4624' => 'Minamikyushu',
		'4625' => 'Isa',
		'4626' => 'Aira',
		'4701' => 'Naha',
		'4704' => 'Ishigaki',
		'4706' => 'Ginowan',
		'4708' => 'Nago',
		'4709' => 'Urasoe',
		'4710' => 'Itoman',
		'4711' => 'Okinawa',
		'4712' => 'Tomigusuku',
		'4713' => 'Uruma',
		'4714' => 'Miyakojima',
		'4715' => 'Nanjo',
	);

	public $jccString = '0101,0102,0103,0104,0105,0106,0107,0108,0109,0110,0111,0112,0113,0114,0115,0116,0117,0118,0119,0120,0121,0122,0123,0124,0125,0126,0127,0128,0129,0130,0131,0133,0134,0135,0136,0201,0202,0203,0204,0205,0206,0207,0208,0209,0210,0301,0302,0303,0304,0305,0307,0308,0309,0310,0311,0313,0314,0315,0316,0401,0402,0403,0404,0406,0407,0409,0410,0411,0412,0413,0414,0415,0501,0502,0503,0504,0505,0506,0507,0508,0509,0510,0511,0512,0513,0601,0602,0603,0605,0606,0607,0608,0609,0611,0612,0613,0614,0615,0616,0701,0702,0703,0705,0707,0708,0711,0714,0715,0717,0718,0719,0720,0801,0802,0804,0805,0806,0808,0809,0810,0811,0812,0813,0816,0818,0822,0823,0824,0825,0826,0827,0828,0901,0902,0903,0904,0905,0906,0907,0908,0909,0910,0911,0912,0913,0914,0915,0918,0919,0920,0921,100101,100102,100103,100104,100105,100106,100107,100108,100109,100110,100111,100112,100113,100114,100115,100116,100117,100118,100119,100120,100121,100122,100123,1002,1003,1004,1005,1006,1007,1008,1009,1010,1011,1012,1013,1014,1015,1016,1019,1020,1021,1022,1023,1024,1025,1026,1028,1029,1030,1101,1102,1103,1104,1105,1106,1107,1108,1109,1110,1111,1112,1113,1114,1115,1116,1117,1118,1119,1201,1202,1203,1204,1205,1206,1207,1208,1210,1211,1212,1213,1215,1216,1217,1218,1219,1220,1221,1222,1223,1224,1225,1226,1227,1228,1229,1230,1231,1232,1233,1234,1235,1236,1237,1238,1239,1302,1303,1304,1306,1307,1308,1309,1310,1311,1312,1314,1315,1316,1317,1318,1319,1321,1322,1323,1324,1325,1327,1328,1329,1330,1331,1332,1333,1334,1336,1337,1338,1339,1340,1341,1342,1343,1344,1345,1346,1401,1402,1403,1404,1405,1407,1408,1410,1412,1414,1415,1416,1417,1419,1420,1421,1422,1423,1424,1425,1426,1427,1428,1429,1430,1431,1432,1433,1434,1435,1436,1437,1501,1502,1503,1504,1505,1506,1508,1509,1510,1511,1513,1514,1515,1516,1601,1602,1603,1604,1605,1606,1607,1608,1609,1610,1611,1612,1701,1702,1704,1705,1706,1707,1708,1709,1710,1711,1712,1713,1714,1801,1802,1803,1805,1806,1807,1808,1809,1811,1812,1813,1814,1815,1816,1817,1820,1821,1822,1823,1824,1825,1826,1827,1901,1902,1903,1904,1905,1906,1907,1908,1909,1910,1911,1912,1913,1914,1915,1916,1917,1918,1919,1920,1921,2001,2002,2003,2004,2005,2006,2007,2008,2009,2010,2011,2012,2013,2014,2015,2016,2017,2019,2021,2022,2023,2024,2025,2026,2027,2028,2029,2030,2031,2032,2033,2034,2035,2036,2037,2038,2039,2040,2101,2102,2103,2104,2105,2107,2108,2109,2110,2111,2112,2115,2116,2117,2201,2202,2203,2204,2205,2206,2207,2208,2209,2210,2211,2212,2213,2214,2215,2301,2302,2303,2304,2306,2307,2308,2309,2310,2311,2312,2313,2314,2401,2402,2403,2404,2405,2406,2407,2408,2409,2410,2411,2412,2501,2502,2503,2504,2506,2507,2508,2509,2510,2511,2512,2513,2514,2515,2516,2517,2518,2521,2522,2523,2524,2525,2526,2527,2528,2529,2530,2531,2532,2533,2534,2535,2536,2601,2602,2603,2604,2605,2606,2607,2608,2609,2701,2702,2703,2704,2705,2706,2707,2708,2709,2710,2711,2713,2714,2715,2716,2717,2718,2719,2720,2721,2723,2724,2725,2726,2727,2728,2729,2730,2731,2801,2802,2804,2805,2806,2807,2808,2809,2810,2811,2901,2902,2904,2905,2906,2907,2908,2909,2910,3001,3002,3003,3004,3005,3006,3007,3009,3010,3011,3012,3101,3102,3103,3104,3107,3109,3110,3111,3112,3113,3114,3115,3116,3117,3118,3201,3202,3203,3204,3205,3206,3207,3209,3301,3302,3303,3304,3306,3307,3308,3310,3311,3312,3313,3315,3316,3401,3402,3403,3404,3501,3502,3503,3504,3505,3508,3509,3510,3511,3512,3513,3514,3515,3516,3601,3602,3603,3604,3605,3606,3607,3608,3701,3702,3703,3704,3705,3706,3707,3708,3801,3802,3803,3804,3805,3806,3807,3810,3813,3814,3815,3901,3902,3903,3904,3905,3907,3908,3909,3910,3911,3912,4001,4007,4008,4009,4010,4011,4012,4015,4016,4017,4018,4019,4020,4021,4022,4023,4024,4025,4026,4027,4029,4030,4031,4032,4033,4034,4035,4036,4037,4101,4102,4103,4104,4105,4106,4107,4108,4109,4110,4201,4202,4203,4204,4205,4207,4208,4209,4210,4211,4212,4213,4214,4301,4302,4303,4304,4305,4306,4308,4310,4311,4312,4313,4314,4315,4316,4401,4402,4403,4404,4405,4406,4407,4408,4410,4411,4412,4413,4414,4415,4501,4502,4503,4504,4505,4506,4507,4508,4509,4601,4603,4604,4606,4607,4610,4614,4615,4616,4617,4618,4619,4620,4621,4622,4623,4624,4625,4626,4701,4704,4706,4708,4709,4710,4711,4712,4713,4714,4715';

	function get_jcc_array($bands, $postdata) {
		$CI =& get_instance();
		$CI->load->model('logbooks_model');
		$logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$jccArray = explode(',', $this->jccString);

		$cities = array(); // Used for keeping track of which cities that are not worked
		foreach ($jccArray as $city) {                         // Generating array for use in the table
			$cities[$city]['count'] = 0;                   // Inits each city's count
		}

        	$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);


		foreach ($bands as $band) {
			foreach ($jccArray as $city) {                   // Generating array for use in the table
				$bandJcc[$city]['Number'] = $city;
				$bandJcc[$city]['City'] = $this->jaCities[$city];
				$bandJcc[$city][$band] = '-';                  // Sets all to dash to indicate no result
			}

			if ($postdata['worked'] != NULL) {
				$jccBand = $this->getJccWorked($location_list, $band, $postdata);
				foreach ($jccBand as $line) {
					$bandJcc[$line->col_cnty][$band] = '<div class="bg-danger awardsBgDanger"><a href=\'javascript:displayContacts("' . $line->col_cnty . '","' . $band . '","'. $postdata['mode'] . '","JCC", "")\'>W</a></div>';
					$cities[$line->col_cnty]['count']++;
				}
			}
			if ($postdata['confirmed'] != NULL) {
				$jccBand = $this->getJccConfirmed($location_list, $band, $postdata);
				foreach ($jccBand as $line) {
					$bandJcc[$line->col_cnty][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . $line->col_cnty . '","' . $band . '","'. $postdata['mode'] . '","JCC", "'.$qsl.'")\'>C</a></div>';
					$cities[$line->col_cnty]['count']++;
				}
			}
		}

		// We want to remove the worked cities in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$jccBand = $this->getJccWorked($location_list, $postdata['band'], $postdata);
			foreach ($jccBand as $line) {
				unset($bandJcc[$line->col_cnty]);
			}
		}

		// We want to remove the confirmed cities in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$wasBand = $this->getJccConfirmed($location_list, $postdata['band'], $postdata);
			foreach ($wasBand as $line) {
				unset($bandJcc[$line->col_cnty]);
			}
		}

		if ($postdata['notworked'] == NULL) {
			foreach ($jccArray as $city) {
				if ($cities[$city]['count'] == 0) {
					unset($bandJcc[$city]);
				};
			}
		}

		if (isset($bandJcc)) {
			return $bandJcc;
		}
		else {
			return 0;
		}
	}

	function getJccBandConfirmed($location_list, $band, $postdata) {
		$sql = "select adif as waja, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql);

		return $query->result();
	}

	function getJccBandWorked($location_list, $band, $postdata) {
		$sql = "select adif as waja, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";;

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$sql .= $this->addContinentsToQuery($postdata);

		$query = $this->db->query($sql);

		return $query->result();
	}

	/*
	 * Function returns all worked, but not confirmed cities
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getJccWorked($location_list, $band, $postdata) {
		$sql = "SELECT distinct col_cnty FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->addStateToQuery();

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= " and not exists (select 1 from ". $this->config->item('table_name') .
			" where station_id in (". $location_list . ")" .
			" and col_cnty = thcv.col_cnty";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addStateToQuery();

		$sql .= ")";

		$query = $this->db->query($sql);

		return $query->result();
	}

	/*
	 * Function returns all confirmed cities on given band and on LoTW or QSL
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getJccConfirmed($location_list, $band, $postdata) {
		$sql = "SELECT distinct col_cnty FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->addStateToQuery();

		$sql .= $this->genfunctions->addBandToQuery($band);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$query = $this->db->query($sql);

		return $query->result();
	}

	
	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 */
	function get_jcc_summary($bands, $postdata)
	{
		$CI =& get_instance();
		$CI->load->model('logbooks_model');
		$logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		foreach ($bands as $band) {
			if ($band != 'SAT') {
				$worked = $this->getSummaryByBand($band, $postdata, $location_list);
				$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $location_list);
				$jccSummary['worked'][$band] = $worked[0]->count;
				$jccSummary['confirmed'][$band] = $confirmed[0]->count;
			}
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $location_list);

		$jccSummary['worked']['Total'] = $workedTotal[0]->count;
		$jccSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		if (in_array('SAT', $bands)) {
				$worked = $this->getSummaryByBand('SAT', $postdata, $location_list);
				$confirmed = $this->getSummaryByBandConfirmed('SAT', $postdata, $location_list);
				$jccSummary['worked']['SAT'] = $worked[0]->count;
				$jccSummary['confirmed']['SAT'] = $confirmed[0]->count;
		}

		return $jccSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list)
	{
		$sql = "SELECT count(distinct thcv.col_cnty) as count FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='" . $band . "'";
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('was');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band ='" . $band . "'";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql);

		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list)
	{
		$sql = "SELECT count(distinct thcv.col_cnty) as count FROM " . $this->config->item('table_name') . " thcv";

		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode ='" . $band . "'";
		} else if ($band == 'All') {
			$this->load->model('bands');

			$bandslots = $this->bands->get_worked_bands('was');

			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band ='" . $band . "'";
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = '" . $postdata['mode'] . "' or col_submode = '" . $postdata['mode'] . "')";
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql);

		return $query->result();
	}


	function addStateToQuery() {
		$sql = '';
		$sql .= " and COL_DXCC in ('339')";
		$sql .= " and (COL_CNTY LIKE '____' OR COL_CNTY LIKE '______')";
		$sql .= " and COL_CNTY in ($this->jccString)";
		return $sql;
	}
}
?>

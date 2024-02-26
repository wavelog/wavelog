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

	function get_jcc_array($bands, $postdata) {
		$CI =& get_instance();
		$CI->load->model('logbooks_model');
		$logbooks_locations_array = $CI->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));

		if (!$logbooks_locations_array) {
			return null;
		}

		$location_list = "'".implode("','",$logbooks_locations_array)."'";

		$jccArray = array_keys($this->jaCities);

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
		$sql .= " and (COL_CNTY LIKE '____' OR COL_CNTY LIKE '10____')";
		$sql .= " and COL_CNTY in (".implode(',', array_keys($this->jaCities)).")";
		return $sql;
	}
}
?>

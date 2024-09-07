<?php

class Jcc_model extends CI_Model {


	private $location_list=null;
	function __construct() {
		$this->load->library('Genfunctions');
		$this->load->model('logbooks_model');
		$logbooks_locations_array = $this->logbooks_model->list_logbook_relationships($this->session->userdata('active_station_logbook'));
		$this->location_list = "'".implode("','",$logbooks_locations_array)."'";
	}

	public $jaCities = array(
		'0101' => array( 'name' => 'Sapporo', 'lat' => 43.061936, 'lon' => 141.3542924),
		'0102' => array( 'name' => 'Asahikawa', 'lat' => 43.7627501, 'lon' => 142.3579263),
		'0103' => array( 'name' => 'Otaru', 'lat' => 43.1906806, 'lon' => 140.9946021),
		'0104' => array( 'name' => 'Hakodate', 'lat' => 41.768793, 'lon' => 140.729008),
		'0105' => array( 'name' => 'Muroran', 'lat' => 42.3152461, 'lon' => 140.9740731),
		'0106' => array( 'name' => 'Kushiro', 'lat' => 42.9906837, 'lon' => 144.3820381),
		'0107' => array( 'name' => 'Obihiro', 'lat' => 42.923809, 'lon' => 143.1966324),
		'0108' => array( 'name' => 'Kitami', 'lat' => 43.8029391, 'lon' => 143.8946351),
		'0109' => array( 'name' => 'Yubari', 'lat' => 43.0563455, 'lon' => 141.9739081),
		'0110' => array( 'name' => 'Iwamizawa', 'lat' => 43.1960638, 'lon' => 141.7753595),
		'0111' => array( 'name' => 'Abashiri', 'lat' => 44.0206027, 'lon' => 144.2732035),
		'0112' => array( 'name' => 'Rumoi', 'lat' => 43.941029, 'lon' => 141.6368171),
		'0113' => array( 'name' => 'Tomakomai', 'lat' => 42.6340602, 'lon' => 141.6055453),
		'0114' => array( 'name' => 'Wakkanai', 'lat' => 45.4158108, 'lon' => 141.6730309),
		'0115' => array( 'name' => 'Bibai', 'lat' => 43.3325763, 'lon' => 141.8537339),
		'0116' => array( 'name' => 'Ashibetsu', 'lat' => 43.518329, 'lon' => 142.1898276),
		'0117' => array( 'name' => 'Ebetsu', 'lat' => 43.1037358, 'lon' => 141.535894),
		'0118' => array( 'name' => 'Akabira', 'lat' => 43.5578505, 'lon' => 142.0440317),
		'0119' => array( 'name' => 'Mombetsu', 'lat' => 44.3565151, 'lon' => 143.3545224),
		'0120' => array( 'name' => 'Shibetsu', 'lat' => 44.1785114, 'lon' => 142.4001645),
		'0121' => array( 'name' => 'Nayoro', 'lat' => 44.3558063, 'lon' => 142.4631619),
		'0122' => array( 'name' => 'Mikasa', 'lat' => 35.2851844, 'lon' => 139.6743413),
		'0123' => array( 'name' => 'Nemuro', 'lat' => 43.3301154, 'lon' => 145.5829068),
		'0124' => array( 'name' => 'Chitose', 'lat' => 42.8209335, 'lon' => 141.6509612),
		'0125' => array( 'name' => 'Takikawa', 'lat' => 43.5577956, 'lon' => 141.9103697),
		'0126' => array( 'name' => 'Sunagawa', 'lat' => 43.494928, 'lon' => 141.9034816),
		'0127' => array( 'name' => 'Utashinai', 'lat' => 43.5213549, 'lon' => 142.0345999),
		'0128' => array( 'name' => 'Fukagawa', 'lat' => 43.7234297, 'lon' => 142.0540685),
		'0129' => array( 'name' => 'Furano', 'lat' => 43.3419744, 'lon' => 142.383188),
		'0130' => array( 'name' => 'Noboribetsu', 'lat' => 42.4127547, 'lon' => 141.1064964),
		'0131' => array( 'name' => 'Eniwa', 'lat' => 42.8827386, 'lon' => 141.5775731),
		'0132' => array( 'name' => 'Kameda', 'lat' => 37.87718, 'lon' => 139.108242),
		'0133' => array( 'name' => 'Date', 'lat' => 42.4717601, 'lon' => 140.8646839),
		'0134' => array( 'name' => 'Kitahiroshima', 'lat' => 42.9853877, 'lon' => 141.5629536),
		'0135' => array( 'name' => 'Ishikari', 'lat' => 43.171677, 'lon' => 141.3159605),
		'0136' => array( 'name' => 'Hokuto', 'lat' => 41.8240228, 'lon' => 140.6529686),
		'0201' => array( 'name' => 'Aomori', 'lat' => 40.886943, 'lon' => 140.590121),
		'0202' => array( 'name' => 'Hirosaki', 'lat' => 40.6030543, 'lon' => 140.4640389),
		'0203' => array( 'name' => 'Hachinohe', 'lat' => 40.5122388, 'lon' => 141.4882959),
		'0204' => array( 'name' => 'Kuroishi', 'lat' => 40.6423427, 'lon' => 140.5951263),
		'0205' => array( 'name' => 'Goshogawara', 'lat' => 40.8076098, 'lon' => 140.4459462),
		'0206' => array( 'name' => 'Towada', 'lat' => 40.6127405, 'lon' => 141.206023),
		'0207' => array( 'name' => 'Misawa', 'lat' => 40.6829377, 'lon' => 141.3692113),
		'0208' => array( 'name' => 'Mutsu', 'lat' => 41.2928444, 'lon' => 141.1831247),
		'0209' => array( 'name' => 'Tsugaru', 'lat' => 40.8087605, 'lon' => 140.3803311),
		'0210' => array( 'name' => 'Hirakawa', 'lat' => 40.5837043, 'lon' => 140.5671226),
		'0301' => array( 'name' => 'Morioka', 'lat' => 39.7021331, 'lon' => 141.1545397),
		'0302' => array( 'name' => 'Kamaishi', 'lat' => 39.2757463, 'lon' => 141.8858151),
		'0303' => array( 'name' => 'Miyako', 'lat' => 39.6395835, 'lon' => 141.9461177),
		'0304' => array( 'name' => 'Ichinoseki', 'lat' => 38.9346961, 'lon' => 141.126605),
		'0305' => array( 'name' => 'Ofunato', 'lat' => 39.0817727, 'lon' => 141.7084037),
		'0306' => array( 'name' => 'Mizusawa', 'lat' => 39.1389814, 'lon' => 141.1464938),
		'0307' => array( 'name' => 'Hanamaki', 'lat' => 39.3884038, 'lon' => 141.1169618),
		'0308' => array( 'name' => 'Kitakami', 'lat' => 39.2866832, 'lon' => 141.1135121),
		'0309' => array( 'name' => 'Kuji', 'lat' => 40.1904323, 'lon' => 141.7756812),
		'0310' => array( 'name' => 'Tono', 'lat' => 39.3306091, 'lon' => 141.5314591),
		'0311' => array( 'name' => 'Rikuzentakata', 'lat' => 39.0204051, 'lon' => 141.6331219),
		'0312' => array( 'name' => 'Esashi', 'lat' => 41.8690714, 'lon' => 140.1272235),
		'0313' => array( 'name' => 'Ninohe', 'lat' => 40.2710819, 'lon' => 141.304674),
		'0314' => array( 'name' => 'Hachimantai', 'lat' => 39.9564706, 'lon' => 141.0709451),
		'0315' => array( 'name' => 'Oshu', 'lat' => 39.144275, 'lon' => 141.1392382),
		'0316' => array( 'name' => 'Takizawa', 'lat' => 39.73477, 'lon' => 141.0770901),
		'0401' => array( 'name' => 'Akita', 'lat' => 39.6898802, 'lon' => 140.342608),
		'0402' => array( 'name' => 'Noshiro', 'lat' => 40.2118417, 'lon' => 140.0271517),
		'0403' => array( 'name' => 'Odate', 'lat' => 40.2716953, 'lon' => 140.5652404),
		'0404' => array( 'name' => 'Yokote', 'lat' => 39.3137847, 'lon' => 140.5667433),
		'0405' => array( 'name' => 'Honjo', 'lat' => 36.2435937, 'lon' => 139.1916278),
		'0406' => array( 'name' => 'Oga', 'lat' => 39.8866202, 'lon' => 139.8473949),
		'0407' => array( 'name' => 'Yuzawa', 'lat' => 39.1643018, 'lon' => 140.4957467),
		'0408' => array( 'name' => 'Omagari', 'lat' => 39.4656823, 'lon' => 140.479891),
		'0409' => array( 'name' => 'Kazuno', 'lat' => 40.2157445, 'lon' => 140.7882193),
		'0410' => array( 'name' => 'Yurihonjo', 'lat' => 39.3858771, 'lon' => 140.0487402),
		'0411' => array( 'name' => 'Katagami', 'lat' => 39.8572974, 'lon' => 140.0132252),
		'0412' => array( 'name' => 'Daisen', 'lat' => 39.4530067, 'lon' => 140.4757635),
		'0413' => array( 'name' => 'Kitaakita', 'lat' => 40.2259431, 'lon' => 140.3709149),
		'0414' => array( 'name' => 'Nikaho', 'lat' => 39.2028587, 'lon' => 139.9076665),
		'0415' => array( 'name' => 'Senboku', 'lat' => 39.7000816, 'lon' => 140.730767),
		'0501' => array( 'name' => 'Yamagata', 'lat' => 38.4746705, 'lon' => 140.083237),
		'0502' => array( 'name' => 'Yonezawa', 'lat' => 37.9222426, 'lon' => 140.1166276),
		'0503' => array( 'name' => 'Tsuruoka', 'lat' => 38.7272187, 'lon' => 139.8266292),
		'0504' => array( 'name' => 'Sakata', 'lat' => 38.9147229, 'lon' => 139.8364101),
		'0505' => array( 'name' => 'Shinjo', 'lat' => 38.7648577, 'lon' => 140.3021449),
		'0506' => array( 'name' => 'Sagae', 'lat' => 38.3808731, 'lon' => 140.2759709),
		'0507' => array( 'name' => 'Kaminoyama', 'lat' => 38.1494956, 'lon' => 140.2677993),
		'0508' => array( 'name' => 'Murayama', 'lat' => 38.4836471, 'lon' => 140.3808616),
		'0509' => array( 'name' => 'Nagai', 'lat' => 38.1074816, 'lon' => 140.0403265),
		'0510' => array( 'name' => 'Tendo', 'lat' => 38.3624343, 'lon' => 140.3772634),
		'0511' => array( 'name' => 'Higashine', 'lat' => 38.4312437, 'lon' => 140.391304),
		'0512' => array( 'name' => 'Obanazawa', 'lat' => 38.6006499, 'lon' => 140.406101),
		'0513' => array( 'name' => 'Nan\'yo', 'lat' => 38.055081, 'lon' => 140.1481134),
		'0601' => array( 'name' => 'Sendai', 'lat' => 38.2677554, 'lon' => 140.8691498),
		'0602' => array( 'name' => 'Ishinomaki', 'lat' => 38.4341281, 'lon' => 141.3028087),
		'0603' => array( 'name' => 'Shiogama', 'lat' => 38.3143384, 'lon' => 141.0218221),
		'0604' => array( 'name' => 'Furukawa', 'lat' => 38.5705529, 'lon' => 140.9679338),
		'0605' => array( 'name' => 'Kesennuma', 'lat' => 38.9080078, 'lon' => 141.5698223),
		'0606' => array( 'name' => 'Shiroishi', 'lat' => 38.0023671, 'lon' => 140.6200214),
		'0607' => array( 'name' => 'Natori', 'lat' => 38.171499, 'lon' => 140.8917335),
		'0608' => array( 'name' => 'Kakuda', 'lat' => 37.9770215, 'lon' => 140.7820521),
		'0609' => array( 'name' => 'Tagajo', 'lat' => 38.2938132, 'lon' => 141.0042642),
		'0610' => array( 'name' => 'Izumi', 'lat' => 34.43108, 'lon' => 135.474789),
		'0611' => array( 'name' => 'Iwanuma', 'lat' => 38.1042874, 'lon' => 140.8701494),
		'0612' => array( 'name' => 'Tome', 'lat' => 38.6918037, 'lon' => 141.1877688),
		'0613' => array( 'name' => 'Kuruhara', 'lat' => 38.73313, 'lon' => 141.02326),
		'0614' => array( 'name' => 'Higashimatsushima', 'lat' => 38.4263868, 'lon' => 141.2110467),
		'0615' => array( 'name' => 'Osaki', 'lat' => 38.5770889, 'lon' => 140.955454),
		'0616' => array( 'name' => 'Tomiya', 'lat' => 38.3998594, 'lon' => 140.8953466),
		'0701' => array( 'name' => 'Fukushima', 'lat' => 37.38158, 'lon' => 140.22199),
		'0702' => array( 'name' => 'Aizuwakamatsu', 'lat' => 37.48043, 'lon' => 139.942135),
		'0703' => array( 'name' => 'Koriyama', 'lat' => 37.404937, 'lon' => 140.333381),
		'0704' => array( 'name' => 'Taira', 'lat' => 32.8719111, 'lon' => 130.3089447),
		'0705' => array( 'name' => 'Shirakawa', 'lat' => 37.1263397, 'lon' => 140.2107192),
		'0706' => array( 'name' => 'Haramachi', 'lat' => 35.7003037, 'lon' => 139.7235922),
		'0707' => array( 'name' => 'Sukagawa', 'lat' => 37.2867857, 'lon' => 140.37293),
		'0708' => array( 'name' => 'Kitakata', 'lat' => 37.6508057, 'lon' => 139.8749387),
		'0709' => array( 'name' => 'Joban', 'lat' => 37.3787847, 'lon' => 140.9664217),
		'0710' => array( 'name' => 'Iwaki', 'lat' => 37.0504227, 'lon' => 140.8876338),
		'0711' => array( 'name' => 'Soma', 'lat' => 37.7966579, 'lon' => 140.9195032),
		'0712' => array( 'name' => 'Uchigo', 'lat' => 37.0357154, 'lon' => 140.8547969),
		'0713' => array( 'name' => 'Nakoso', 'lat' => 36.8839897, 'lon' => 140.7868497),
		'0714' => array( 'name' => 'Nihonmatsu', 'lat' => 37.5850396, 'lon' => 140.4313889),
		'0715' => array( 'name' => 'Iwaki', 'lat' => 37.0504227, 'lon' => 140.8876338),
		'0716' => array( 'name' => 'Wakamatsu', 'lat' => 33.9009962, 'lon' => 130.8060685),
		'0717' => array( 'name' => 'Tamura', 'lat' => 37.4405603, 'lon' => 140.5764547),
		'0718' => array( 'name' => 'Minamisoma', 'lat' => 37.6421923, 'lon' => 140.9572649),
		'0719' => array( 'name' => 'Date', 'lat' => 42.4717601, 'lon' => 140.8646839),
		'0720' => array( 'name' => 'Motomiya', 'lat' => 37.5141768, 'lon' => 140.3994933),
		'0801' => array( 'name' => 'Niigata', 'lat' => 37.6452283, 'lon' => 138.7669125),
		'0802' => array( 'name' => 'Nagaoka', 'lat' => 37.446996, 'lon' => 138.8512199),
		'0803' => array( 'name' => 'Takada', 'lat' => 34.5162814, 'lon' => 135.7446939),
		'0804' => array( 'name' => 'Sanjo', 'lat' => 37.6361174, 'lon' => 138.9613971),
		'0805' => array( 'name' => 'Kashiwazaki', 'lat' => 37.3719095, 'lon' => 138.5591406),
		'0806' => array( 'name' => 'Shibata', 'lat' => 37.9478881, 'lon' => 139.3271831),
		'0807' => array( 'name' => 'Niitsu', 'lat' => 37.8000262, 'lon' => 139.1209935),
		'0808' => array( 'name' => 'Ojiya', 'lat' => 37.3142715, 'lon' => 138.7951327),
		'0809' => array( 'name' => 'Kamo', 'lat' => 37.6661851, 'lon' => 139.04018),
		'0810' => array( 'name' => 'Tokamachi', 'lat' => 37.1276085, 'lon' => 138.755504),
		'0811' => array( 'name' => 'Mitsuke', 'lat' => 37.53145, 'lon' => 138.9126572),
		'0812' => array( 'name' => 'Murakami', 'lat' => 38.3144518, 'lon' => 139.5751176),
		'0813' => array( 'name' => 'Tsubame', 'lat' => 37.6730751, 'lon' => 138.8825389),
		'0814' => array( 'name' => 'Naoetsu', 'lat' => 37.170264, 'lon' => 138.2422616),
		'0815' => array( 'name' => 'Tochio', 'lat' => 35.532446, 'lon' => 135.4364781),
		'0816' => array( 'name' => 'Itoigawa', 'lat' => 37.0433062, 'lon' => 137.8617531),
		'0817' => array( 'name' => 'Arai', 'lat' => 34.7578437, 'lon' => 134.7936014),
		'0818' => array( 'name' => 'Gosen', 'lat' => 37.7444474, 'lon' => 139.1826005),
		'0819' => array( 'name' => 'Ryotsu', 'lat' => 38.0810217, 'lon' => 138.4374641),
		'0820' => array( 'name' => 'Shirone', 'lat' => 37.776335, 'lon' => 139.0245333),
		'0821' => array( 'name' => 'Toyosaka', 'lat' => 37.9197443, 'lon' => 139.2156646),
		'0822' => array( 'name' => 'Joetsu', 'lat' => 37.1478816, 'lon' => 138.2359501),
		'0823' => array( 'name' => 'Agano', 'lat' => 37.8343618, 'lon' => 139.2258539),
		'0824' => array( 'name' => 'Sado', 'lat' => 38.0182578, 'lon' => 138.3683995),
		'0825' => array( 'name' => 'Uonuma', 'lat' => 37.2303274, 'lon' => 138.9611531),
		'0826' => array( 'name' => 'Minamiuonuma', 'lat' => 37.0655723, 'lon' => 138.8760989),
		'0827' => array( 'name' => 'Myoko', 'lat' => 37.0252467, 'lon' => 138.253635),
		'0828' => array( 'name' => 'Tainai', 'lat' => 38.0596893, 'lon' => 139.4102658),
		'0901' => array( 'name' => 'Nagano', 'lat' => 36.1143945, 'lon' => 138.0319015),
		'0902' => array( 'name' => 'Matsumoto', 'lat' => 36.2382047, 'lon' => 137.9687141),
		'0903' => array( 'name' => 'Ueda', 'lat' => 36.4021192, 'lon' => 138.2490506),
		'0904' => array( 'name' => 'Okaya', 'lat' => 36.07853, 'lon' => 138.049549),
		'0905' => array( 'name' => 'Iida', 'lat' => 35.5147101, 'lon' => 137.8219519),
		'0906' => array( 'name' => 'Suwa', 'lat' => 36.03209, 'lon' => 138.114118),
		'0907' => array( 'name' => 'Suzaka', 'lat' => 36.6510923, 'lon' => 138.3071289),
		'0908' => array( 'name' => 'Komoro', 'lat' => 36.3272526, 'lon' => 138.4259718),
		'0909' => array( 'name' => 'Ina', 'lat' => 35.830452, 'lon' => 137.954916),
		'0910' => array( 'name' => 'Komagane', 'lat' => 35.7296709, 'lon' => 137.9389254),
		'0911' => array( 'name' => 'Nakano', 'lat' => 35.718123, 'lon' => 139.664468),
		'0912' => array( 'name' => 'Omachi', 'lat' => 36.5029093, 'lon' => 137.8508885),
		'0913' => array( 'name' => 'Iiyama', 'lat' => 36.8517629, 'lon' => 138.3654364),
		'0914' => array( 'name' => 'Chino', 'lat' => 36.02598, 'lon' => 138.24379),
		'0915' => array( 'name' => 'Shiojiri', 'lat' => 36.124957, 'lon' => 137.952801),
		'0916' => array( 'name' => 'Shinonoi', 'lat' => 36.5774099, 'lon' => 138.1382288),
		'0917' => array( 'name' => 'Koshoku', 'lat' => 36.5179021, 'lon' => 138.0954697),
		'0918' => array( 'name' => 'Saku', 'lat' => 36.2488014, 'lon' => 138.4767695),
		'0919' => array( 'name' => 'Chikuma', 'lat' => 36.5336984, 'lon' => 138.120123),
		'0920' => array( 'name' => 'Tomi', 'lat' => 36.3594225, 'lon' => 138.3305353),
		'0921' => array( 'name' => 'Azumino', 'lat' => 36.3044083, 'lon' => 137.9054972),
		'100101' => array( 'name' => 'Chiyoda', 'lat' => 35.6938097, 'lon' => 139.7532163),
		'100102' => array( 'name' => 'Chuo', 'lat' => 35.666255, 'lon' => 139.775565),
		'100103' => array( 'name' => 'Minato', 'lat' => 35.6432274, 'lon' => 139.7400553),
		'100104' => array( 'name' => 'Shinjuku', 'lat' => 35.6937632, 'lon' => 139.7036319),
		'100105' => array( 'name' => 'Bunkyo', 'lat' => 35.71881, 'lon' => 139.744732),
		'100106' => array( 'name' => 'Taito', 'lat' => 35.71745, 'lon' => 139.790859),
		'100107' => array( 'name' => 'Sumida', 'lat' => 35.7003789, 'lon' => 139.8058673),
		'100108' => array( 'name' => 'Koto', 'lat' => 35.6727747, 'lon' => 139.8169621),
		'100109' => array( 'name' => 'Shinagawa', 'lat' => 35.599252, 'lon' => 139.73891),
		'100110' => array( 'name' => 'Meguro', 'lat' => 35.62125, 'lon' => 139.688014),
		'100111' => array( 'name' => 'Ota', 'lat' => 35.561206, 'lon' => 139.715843),
		'100112' => array( 'name' => 'Setagaya', 'lat' => 35.646096, 'lon' => 139.65627),
		'100113' => array( 'name' => 'Shibuya', 'lat' => 35.6645956, 'lon' => 139.6987107),
		'100114' => array( 'name' => 'Nakano', 'lat' => 35.718123, 'lon' => 139.664468),
		'100115' => array( 'name' => 'Suginami', 'lat' => 35.6994929, 'lon' => 139.6362876),
		'100116' => array( 'name' => 'Toshima', 'lat' => 35.736156, 'lon' => 139.714222),
		'100117' => array( 'name' => 'Kita', 'lat' => 35.755838, 'lon' => 139.736687),
		'100118' => array( 'name' => 'Arakawa', 'lat' => 35.737529, 'lon' => 139.78131),
		'100119' => array( 'name' => 'Itabashi', 'lat' => 35.774143, 'lon' => 139.681209),
		'100120' => array( 'name' => 'Nerima', 'lat' => 35.74836, 'lon' => 139.638735),
		'100121' => array( 'name' => 'Adachi', 'lat' => 35.783703, 'lon' => 139.795319),
		'100122' => array( 'name' => 'Katsushika', 'lat' => 35.751733, 'lon' => 139.863816),
		'100123' => array( 'name' => 'Edogawa', 'lat' => 35.678278, 'lon' => 139.871091),
		'1001' => array( 'name' => 'Tokyo', 'lat' => 35.6821936, 'lon' => 139.762221),
		'1002' => array( 'name' => 'Hachioji', 'lat' => 35.655389, 'lon' => 139.3394669),
		'1003' => array( 'name' => 'Tachikawa', 'lat' => 35.724463, 'lon' => 139.404766),
		'1004' => array( 'name' => 'Musashino', 'lat' => 35.712898, 'lon' => 139.563534),
		'1005' => array( 'name' => 'Mitaka', 'lat' => 35.685227, 'lon' => 139.572916),
		'1006' => array( 'name' => 'Ome', 'lat' => 35.803601, 'lon' => 139.238128),
		'1007' => array( 'name' => 'Fuchu', 'lat' => 34.5683141, 'lon' => 133.2366327),
		'1008' => array( 'name' => 'Akishima', 'lat' => 35.70248, 'lon' => 139.350065),
		'1009' => array( 'name' => 'Chofu', 'lat' => 35.660036, 'lon' => 139.554815),
		'1010' => array( 'name' => 'Machida', 'lat' => 35.564193, 'lon' => 139.442839),
		'1011' => array( 'name' => 'Koganei', 'lat' => 35.7041083, 'lon' => 139.5106759),
		'1012' => array( 'name' => 'Kodaira', 'lat' => 35.72522, 'lon' => 139.476606),
		'1013' => array( 'name' => 'Hino', 'lat' => 35.66314, 'lon' => 139.39859),
		'1014' => array( 'name' => 'Higashimurayama', 'lat' => 35.768929, 'lon' => 139.484539),
		'1015' => array( 'name' => 'Kokubunji', 'lat' => 35.709674, 'lon' => 139.454224),
		'1016' => array( 'name' => 'Kunitachi', 'lat' => 35.681991, 'lon' => 139.43624),
		'1017' => array( 'name' => 'Hoya', 'lat' => 36.37353, 'lon' => 138.19690),
		'1018' => array( 'name' => 'Tanashi', 'lat' => 35.7273146, 'lon' => 139.5394387),
		'1019' => array( 'name' => 'Fussa', 'lat' => 35.7423308, 'lon' => 139.3278791),
		'1020' => array( 'name' => 'Komae', 'lat' => 35.634023, 'lon' => 139.575977),
		'1021' => array( 'name' => 'Higashiyamato', 'lat' => 35.740869, 'lon' => 139.428831),
		'1022' => array( 'name' => 'Kiyose', 'lat' => 35.785483, 'lon' => 139.531253),
		'1023' => array( 'name' => 'Higashikurume', 'lat' => 35.752546, 'lon' => 139.519089),
		'1024' => array( 'name' => 'Musashimurayama', 'lat' => 35.756509, 'lon' => 139.385637),
		'1025' => array( 'name' => 'Tama', 'lat' => 35.63098, 'lon' => 139.43983),
		'1026' => array( 'name' => 'Inagi', 'lat' => 35.638229, 'lon' => 139.507776),
		'1027' => array( 'name' => 'Akigawa', 'lat' => 35.728075, 'lon' => 139.2866763),
		'1028' => array( 'name' => 'Hamura', 'lat' => 35.764833, 'lon' => 139.307862),
		'1029' => array( 'name' => 'Akiruno', 'lat' => 35.731042, 'lon' => 139.217028),
		'1030' => array( 'name' => 'Nishitokyo', 'lat' => 35.73546, 'lon' => 139.550228),
		'1101' => array( 'name' => 'Yokohama', 'lat' => 35.4443947, 'lon' => 139.6367727),
		'1102' => array( 'name' => 'Yokosuka', 'lat' => 35.2730564, 'lon' => 139.6653829),
		'1103' => array( 'name' => 'Kawasaki', 'lat' => 35.5305307, 'lon' => 139.7028012),
		'1104' => array( 'name' => 'Hiratsuka', 'lat' => 35.357674, 'lon' => 139.318278),
		'1105' => array( 'name' => 'Kamakura', 'lat' => 35.329564, 'lon' => 139.54442),
		'1106' => array( 'name' => 'Fujisawa', 'lat' => 35.364842, 'lon' => 139.465077),
		'1107' => array( 'name' => 'Odawara', 'lat' => 35.263676, 'lon' => 139.150229),
		'1108' => array( 'name' => 'Chigasaki', 'lat' => 35.329479, 'lon' => 139.405371),
		'1109' => array( 'name' => 'Zushi', 'lat' => 35.3040672, 'lon' => 139.5838447),
		'1110' => array( 'name' => 'Sagamihara', 'lat' => 35.56559, 'lon' => 139.236215),
		'1111' => array( 'name' => 'Miura', 'lat' => 35.1550499, 'lon' => 139.6406823),
		'1112' => array( 'name' => 'Hadano', 'lat' => 35.3746619, 'lon' => 139.2420729),
		'1113' => array( 'name' => 'Atsugi', 'lat' => 35.4433894, 'lon' => 139.3783891),
		'1114' => array( 'name' => 'Yamato', 'lat' => 32.699616, 'lon' => 131.049859),
		'1115' => array( 'name' => 'Isehara', 'lat' => 35.4023968, 'lon' => 139.2996106),
		'1116' => array( 'name' => 'Ebina', 'lat' => 35.4527491, 'lon' => 139.3909319),
		'1117' => array( 'name' => 'Zama', 'lat' => 35.4865288, 'lon' => 139.3877657),
		'1118' => array( 'name' => 'Minamiashigara', 'lat' => 35.3205738, 'lon' => 139.0992405),
		'1119' => array( 'name' => 'Ayase', 'lat' => 35.4460475, 'lon' => 139.430823),
		'1201' => array( 'name' => 'Chiba', 'lat' => 35.549399, 'lon' => 140.2647303),
		'1202' => array( 'name' => 'Choshi', 'lat' => 35.7345338, 'lon' => 140.8272667),
		'1203' => array( 'name' => 'Ichikawa', 'lat' => 35.729412, 'lon' => 139.928568),
		'1204' => array( 'name' => 'Funabashi', 'lat' => 35.699997, 'lon' => 139.988668),
		'1205' => array( 'name' => 'Tateyama', 'lat' => 34.9965304, 'lon' => 139.8699838),
		'1206' => array( 'name' => 'Kisarazu', 'lat' => 35.3810808, 'lon' => 139.9247334),
		'1207' => array( 'name' => 'Matsudo', 'lat' => 35.7879371, 'lon' => 139.903177),
		'1208' => array( 'name' => 'Noda', 'lat' => 35.9549076, 'lon' => 139.8748667),
		'1209' => array( 'name' => 'Sawara', 'lat' => 35.8945456, 'lon' => 140.4940774),
		'1210' => array( 'name' => 'Mobara', 'lat' => 35.4285094, 'lon' => 140.2880753),
		'1211' => array( 'name' => 'Narita', 'lat' => 35.7767683, 'lon' => 140.3183376),
		'1212' => array( 'name' => 'Sakura', 'lat' => 35.7234619, 'lon' => 140.2240158),
		'1213' => array( 'name' => 'Togane', 'lat' => 35.5600309, 'lon' => 140.3662589),
		'1214' => array( 'name' => 'Yokaichiba', 'lat' => 35.6992256, 'lon' => 140.5520936),
		'1215' => array( 'name' => 'Asahi', 'lat' => 35.7204126, 'lon' => 140.6464527),
		'1216' => array( 'name' => 'Narashino', 'lat' => 35.670572, 'lon' => 140.018956),
		'1217' => array( 'name' => 'Kashiwa', 'lat' => 35.8676218, 'lon' => 139.9756876),
		'1218' => array( 'name' => 'Katsuura', 'lat' => 35.1521846, 'lon' => 140.3207449),
		'1219' => array( 'name' => 'Ichihara', 'lat' => 35.497775, 'lon' => 140.1156996),
		'1220' => array( 'name' => 'Nagareyama', 'lat' => 35.8562314, 'lon' => 139.9026259),
		'1221' => array( 'name' => 'Yachiyo', 'lat' => 35.722537, 'lon' => 140.0995131),
		'1222' => array( 'name' => 'Abiko', 'lat' => 35.863999, 'lon' => 140.0280653),
		'1223' => array( 'name' => 'Kamogawa', 'lat' => 35.1140584, 'lon' => 140.098692),
		'1224' => array( 'name' => 'Kimitsu', 'lat' => 35.3302375, 'lon' => 139.902551),
		'1225' => array( 'name' => 'Kamagaya', 'lat' => 35.7766455, 'lon' => 140.0007147),
		'1226' => array( 'name' => 'Futtu', 'lat' => 35.22665, 'lon' => 139.89873),
		'1227' => array( 'name' => 'Urayasu', 'lat' => 35.6530518, 'lon' => 139.9018495),
		'1228' => array( 'name' => 'Yotsukaido', 'lat' => 35.6696551, 'lon' => 140.1679445),
		'1229' => array( 'name' => 'Sodegaura', 'lat' => 35.4296688, 'lon' => 139.9544661),
		'1230' => array( 'name' => 'Yachimata', 'lat' => 35.6658607, 'lon' => 140.3178646),
		'1231' => array( 'name' => 'Inzai', 'lat' => 35.8322582, 'lon' => 140.1452981),
		'1232' => array( 'name' => 'Shiroi', 'lat' => 35.7914538, 'lon' => 140.0560632),
		'1233' => array( 'name' => 'Tomisato', 'lat' => 35.7268876, 'lon' => 140.3430548),
		'1234' => array( 'name' => 'Minamiboso', 'lat' => 35.0387486, 'lon' => 139.8371399),
		'1235' => array( 'name' => 'Sosa', 'lat' => 35.70794, 'lon' => 140.5645144),
		'1236' => array( 'name' => 'Katori', 'lat' => 35.8978273, 'lon' => 140.4992787),
		'1237' => array( 'name' => 'Sanmu', 'lat' => 35.484561, 'lon' => 140.540184),
		'1238' => array( 'name' => 'Isumi', 'lat' => 35.2539394, 'lon' => 140.3849461),
		'1239' => array( 'name' => 'Oamishirasato', 'lat' => 35.5216038, 'lon' => 140.3208929),
		'1301' => array( 'name' => 'Urawa', 'lat' => 35.8589883, 'lon' => 139.6571882),
		'1302' => array( 'name' => 'Kawagoe', 'lat' => 35.9251145, 'lon' => 139.4856927),
		'1303' => array( 'name' => 'Kumagaya', 'lat' => 36.1472472, 'lon' => 139.3886141),
		'1304' => array( 'name' => 'Kawaguchi', 'lat' => 35.8078228, 'lon' => 139.7241054),
		'1305' => array( 'name' => 'Omiya', 'lat' => 35.9063869, 'lon' => 139.6243304),
		'1306' => array( 'name' => 'Gyoda', 'lat' => 36.1386052, 'lon' => 139.4559001),
		'1307' => array( 'name' => 'Chichibu', 'lat' => 35.9914509, 'lon' => 139.0857612),
		'1308' => array( 'name' => 'Tokorozawa', 'lat' => 35.7994271, 'lon' => 139.4687478),
		'1309' => array( 'name' => 'Hanno', 'lat' => 35.8556902, 'lon' => 139.3276436),
		'1310' => array( 'name' => 'Kazo', 'lat' => 36.1308572, 'lon' => 139.603225),
		'1311' => array( 'name' => 'Honjo', 'lat' => 36.2435937, 'lon' => 139.1916278),
		'1312' => array( 'name' => 'Higashimatsuyama', 'lat' => 36.0421523, 'lon' => 139.399796),
		'1313' => array( 'name' => 'Iwatsuki', 'lat' => 35.9500131, 'lon' => 139.6943932),
		'1314' => array( 'name' => 'Kasukabe', 'lat' => 35.9757957, 'lon' => 139.752019),
		'1315' => array( 'name' => 'Sayama', 'lat' => 35.8528971, 'lon' => 139.4122999),
		'1316' => array( 'name' => 'Hanyu', 'lat' => 36.1724023, 'lon' => 139.5484797),
		'1317' => array( 'name' => 'Konosu', 'lat' => 36.0657583, 'lon' => 139.5221055),
		'1318' => array( 'name' => 'Fukaya', 'lat' => 36.1915583, 'lon' => 139.2810987),
		'1319' => array( 'name' => 'Ageo', 'lat' => 35.9774082, 'lon' => 139.5930504),
		'1320' => array( 'name' => 'Yono', 'lat' => 35.8843981, 'lon' => 139.6391522),
		'1321' => array( 'name' => 'Soka', 'lat' => 35.8262233, 'lon' => 139.8061784),
		'1322' => array( 'name' => 'Koshigaya', 'lat' => 35.8903993, 'lon' => 139.7908633),
		'1323' => array( 'name' => 'Warabi', 'lat' => 35.8263705, 'lon' => 139.6791302),
		'1324' => array( 'name' => 'Toda', 'lat' => 35.8175874, 'lon' => 139.6778944),
		'1325' => array( 'name' => 'Iruma', 'lat' => 35.8358142, 'lon' => 139.3909293),
		'1326' => array( 'name' => 'Hatogaya', 'lat' => 35.8309021, 'lon' => 139.7362167),
		'1327' => array( 'name' => 'Asaka', 'lat' => 35.7970861, 'lon' => 139.593733),
		'1328' => array( 'name' => 'Shiki', 'lat' => 35.8373892, 'lon' => 139.5795684),
		'1329' => array( 'name' => 'Wako', 'lat' => 35.7817053, 'lon' => 139.6058692),
		'1330' => array( 'name' => 'Niiza', 'lat' => 35.7931194, 'lon' => 139.5657258),
		'1331' => array( 'name' => 'Okegawa', 'lat' => 36.0028937, 'lon' => 139.5583422),
		'1332' => array( 'name' => 'Kuki', 'lat' => 36.0618828, 'lon' => 139.6667081),
		'1333' => array( 'name' => 'Kitamoto', 'lat' => 36.0268711, 'lon' => 139.5301389),
		'1334' => array( 'name' => 'Yashio', 'lat' => 35.8226404, 'lon' => 139.8386867),
		'1335' => array( 'name' => 'Kamifukuoka', 'lat' => 35.870999, 'lon' => 139.51),
		'1336' => array( 'name' => 'Fujimi', 'lat' => 35.9083257, 'lon' => 138.2026202),
		'1337' => array( 'name' => 'Misato', 'lat' => 35.8289993, 'lon' => 139.8726811),
		'1338' => array( 'name' => 'Hasuda', 'lat' => 35.994092, 'lon' => 139.6632547),
		'1339' => array( 'name' => 'Sakado', 'lat' => 35.9572312, 'lon' => 139.4029048),
		'1340' => array( 'name' => 'Satte', 'lat' => 36.0778827, 'lon' => 139.7254086),
		'1341' => array( 'name' => 'Tsurugashima', 'lat' => 35.9346812, 'lon' => 139.3929735),
		'1342' => array( 'name' => 'Hidaka', 'lat' => 35.9077667, 'lon' => 139.3390385),
		'1343' => array( 'name' => 'Yoshikawa', 'lat' => 35.8962831, 'lon' => 139.854504),
		'1344' => array( 'name' => 'Saitama', 'lat' => 35.9754168, 'lon' => 139.4160114),
		'1345' => array( 'name' => 'Fujimino', 'lat' => 35.8794749, 'lon' => 139.5195441),
		'1346' => array( 'name' => 'Shiraoka', 'lat' => 36.0186181, 'lon' => 139.677099),
		'1401' => array( 'name' => 'Mito', 'lat' => 36.3659174, 'lon' => 140.4731743),
		'1402' => array( 'name' => 'Hitachi', 'lat' => 36.5991225, 'lon' => 140.6504604),
		'1403' => array( 'name' => 'Tsuchiura', 'lat' => 36.0786297, 'lon' => 140.2045934),
		'1404' => array( 'name' => 'Koga', 'lat' => 36.178025, 'lon' => 139.7553638),
		'1405' => array( 'name' => 'Ishioka', 'lat' => 36.1908398, 'lon' => 140.2884101),
		'1406' => array( 'name' => 'Shimodate', 'lat' => 36.3041016, 'lon' => 139.9780655),
		'1407' => array( 'name' => 'Yuki', 'lat' => 36.3052945, 'lon' => 139.8771403),
		'1408' => array( 'name' => 'Ryugasaki', 'lat' => 35.9113158, 'lon' => 140.181878),
		'1409' => array( 'name' => 'Nakaminato', 'lat' => 36.3450819, 'lon' => 140.5881169),
		'1410' => array( 'name' => 'Shimotsuma', 'lat' => 36.1843552, 'lon' => 139.9672418),
		'1411' => array( 'name' => 'Mitsukaido', 'lat' => 36.0180569, 'lon' => 139.9916513),
		'1412' => array( 'name' => 'Hitachiota', 'lat' => 36.5373021, 'lon' => 140.5308393),
		'1413' => array( 'name' => 'Katsuta', 'lat' => 36.3944346, 'lon' => 140.5242433),
		'1414' => array( 'name' => 'Takahagi', 'lat' => 36.7199765, 'lon' => 140.7158414),
		'1415' => array( 'name' => 'Kitaibaraki', 'lat' => 36.8018507, 'lon' => 140.7513188),
		'1416' => array( 'name' => 'Kasama', 'lat' => 36.3452244, 'lon' => 140.3042261),
		'1417' => array( 'name' => 'Toride', 'lat' => 35.9112034, 'lon' => 140.0500352),
		'1418' => array( 'name' => 'Iwai', 'lat' => 35.0926346, 'lon' => 139.8496343),
		'1419' => array( 'name' => 'Ushiku', 'lat' => 35.9790551, 'lon' => 140.1495982),
		'1420' => array( 'name' => 'Tsukuba', 'lat' => 36.0833265, 'lon' => 140.077279),
		'1421' => array( 'name' => 'Hitachinaka', 'lat' => 36.3961235, 'lon' => 140.5353397),
		'1422' => array( 'name' => 'Kashima', 'lat' => 35.9661164, 'lon' => 140.6450292),
		'1423' => array( 'name' => 'Itako', 'lat' => 35.9471731, 'lon' => 140.5552819),
		'1424' => array( 'name' => 'Moriya', 'lat' => 35.9510096, 'lon' => 139.9754981),
		'1425' => array( 'name' => 'Hitachiomiya', 'lat' => 36.5429197, 'lon' => 140.4116172),
		'1426' => array( 'name' => 'Naka', 'lat' => 36.457227, 'lon' => 140.4871772),
		'1427' => array( 'name' => 'Chikusei', 'lat' => 36.3051944, 'lon' => 139.9790903),
		'1428' => array( 'name' => 'Bandou', 'lat' => 36.06639, 'lon' => 139.88729),
		'1429' => array( 'name' => 'Inashiki', 'lat' => 35.9720697, 'lon' => 140.3033759),
		'1430' => array( 'name' => 'Kasumigaura', 'lat' => 36.1552115, 'lon' => 140.2353307),
		'1431' => array( 'name' => 'Sakuragawa', 'lat' => 36.327241, 'lon' => 140.0903587),
		'1432' => array( 'name' => 'Kamisu', 'lat' => 35.8898999, 'lon' => 140.6645754),
		'1433' => array( 'name' => 'Namegata', 'lat' => 35.9901895, 'lon' => 140.4888826),
		'1434' => array( 'name' => 'Hokota', 'lat' => 36.1585645, 'lon' => 140.5165158),
		'1435' => array( 'name' => 'Joso', 'lat' => 36.0235685, 'lon' => 139.9938714),
		'1436' => array( 'name' => 'Tsukubamirai', 'lat' => 35.9627445, 'lon' => 140.0377674),
		'1437' => array( 'name' => 'Omitama', 'lat' => 36.2389756, 'lon' => 140.3523684),
		'1501' => array( 'name' => 'Utsunomiya', 'lat' => 36.5549677, 'lon' => 139.8828776),
		'1502' => array( 'name' => 'Ashikaga', 'lat' => 36.3401914, 'lon' => 139.4497731),
		'1503' => array( 'name' => 'Tochigi', 'lat' => 36.6782167, 'lon' => 139.8096549),
		'1504' => array( 'name' => 'Sano', 'lat' => 36.3144119, 'lon' => 139.578429),
		'1505' => array( 'name' => 'Kanuma', 'lat' => 36.5682281, 'lon' => 139.7457781),
		'1506' => array( 'name' => 'Nikko', 'lat' => 36.846433, 'lon' => 139.5582717),
		'1507' => array( 'name' => 'Imaichi', 'lat' => 36.720468, 'lon' => 139.6872715),
		'1508' => array( 'name' => 'Oyama', 'lat' => 36.315537, 'lon' => 139.8006952),
		'1509' => array( 'name' => 'Mooka', 'lat' => 36.4390901, 'lon' => 140.0128896),
		'1510' => array( 'name' => 'Otawara', 'lat' => 36.87103, 'lon' => 140.0154048),
		'1511' => array( 'name' => 'Yaita', 'lat' => 36.8066705, 'lon' => 139.9236292),
		'1512' => array( 'name' => 'Kuroiso', 'lat' => 36.9701908, 'lon' => 140.0601121),
		'1513' => array( 'name' => 'Nasushiobara', 'lat' => 36.9621788, 'lon' => 140.0467207),
		'1514' => array( 'name' => 'Sakura', 'lat' => 35.7234619, 'lon' => 140.2240158),
		'1515' => array( 'name' => 'Nasukarasuyama', 'lat' => 36.657187, 'lon' => 140.1518102),
		'1516' => array( 'name' => 'Shimotsuke', 'lat' => 36.3943469, 'lon' => 139.8515863),
		'1601' => array( 'name' => 'Maebashi', 'lat' => 36.3893418, 'lon' => 139.0632826),
		'1602' => array( 'name' => 'Takasaki', 'lat' => 36.3220984, 'lon' => 139.0032758),
		'1603' => array( 'name' => 'Kiryu', 'lat' => 36.4055296, 'lon' => 139.3310209),
		'1604' => array( 'name' => 'Isesaki', 'lat' => 36.3111734, 'lon' => 139.1968083),
		'1605' => array( 'name' => 'Ota', 'lat' => 35.561206, 'lon' => 139.715843),
		'1606' => array( 'name' => 'Numata', 'lat' => 36.6440697, 'lon' => 139.0428829),
		'1607' => array( 'name' => 'Tatebayashi', 'lat' => 36.2454338, 'lon' => 139.5421576),
		'1608' => array( 'name' => 'Shibukawa', 'lat' => 36.4894606, 'lon' => 139.0001287),
		'1609' => array( 'name' => 'Fujioka', 'lat' => 36.258633, 'lon' => 139.0745021),
		'1610' => array( 'name' => 'Tomioka', 'lat' => 36.2598266, 'lon' => 138.8899792),
		'1611' => array( 'name' => 'Annaka', 'lat' => 36.3263653, 'lon' => 138.8878314),
		'1612' => array( 'name' => 'Midori', 'lat' => 35.8713637, 'lon' => 139.6839185),
		'1701' => array( 'name' => 'Kofu', 'lat' => 35.6652481, 'lon' => 138.5710441),
		'1702' => array( 'name' => 'Fujiyoshida', 'lat' => 35.4835487, 'lon' => 138.7958212),
		'1703' => array( 'name' => 'Enzan', 'lat' => 35.7054703, 'lon' => 138.7341835),
		'1704' => array( 'name' => 'Tsuru', 'lat' => 35.5516184, 'lon' => 138.9054872),
		'1705' => array( 'name' => 'Yamanashi', 'lat' => 35.6399328, 'lon' => 138.6380495),
		'1706' => array( 'name' => 'Otsuki', 'lat' => 35.6128834, 'lon' => 138.9429092),
		'1707' => array( 'name' => 'Nirasaki', 'lat' => 35.717518, 'lon' => 138.409373),
		'1708' => array( 'name' => 'Minami-Alps', 'lat' => 35.6083617, 'lon' => 138.4649893),
		'1709' => array( 'name' => 'Hokuto', 'lat' => 41.8240228, 'lon' => 140.6529686),
		'1710' => array( 'name' => 'Kai', 'lat' => 35.668167, 'lon' => 138.515327),
		'1711' => array( 'name' => 'Fuehuki', 'lat' => 35.60117, 'lon' => 138.68119),
		'1712' => array( 'name' => 'Uenohara', 'lat' => 35.632505, 'lon' => 139.08775),
		'1713' => array( 'name' => 'Koshu', 'lat' => 35.726318, 'lon' => 138.793924),
		'1714' => array( 'name' => 'Chuo', 'lat' => 35.666255, 'lon' => 139.775565),
		'1801' => array( 'name' => 'Shizuoka', 'lat' => 34.9332488, 'lon' => 138.0955398),
		'1802' => array( 'name' => 'Hamamatsu', 'lat' => 34.7109786, 'lon' => 137.7259431),
		'1803' => array( 'name' => 'Numadu', 'lat' => 35.094699, 'lon' => 138.866742),
		'1804' => array( 'name' => 'Shimizu', 'lat' => 35.10764, 'lon' => 138.898974),
		'1805' => array( 'name' => 'Atami', 'lat' => 35.08992, 'lon' => 139.059891),
		'1806' => array( 'name' => 'Mishima', 'lat' => 35.147361, 'lon' => 138.948903),
		'1807' => array( 'name' => 'Fujinomiya', 'lat' => 35.2221369, 'lon' => 138.6214683),
		'1808' => array( 'name' => 'Ito', 'lat' => 34.926734, 'lon' => 139.087685),
		'1809' => array( 'name' => 'Shimada', 'lat' => 34.879912, 'lon' => 138.146599),
		'1810' => array( 'name' => 'Yoshiwara', 'lat' => 35.1439052, 'lon' => 138.7023368),
		'1811' => array( 'name' => 'Iwata', 'lat' => 34.706481, 'lon' => 137.851285),
		'1812' => array( 'name' => 'Yaidu', 'lat' => 34.86877, 'lon' => 138.31952),
		'1813' => array( 'name' => 'Fuji', 'lat' => 35.362799, 'lon' => 138.730781),
		'1814' => array( 'name' => 'Kakegawa', 'lat' => 34.793469, 'lon' => 138.018733),
		'1815' => array( 'name' => 'Fujieda', 'lat' => 34.8493225, 'lon' => 138.2522508),
		'1816' => array( 'name' => 'Gotemba', 'lat' => 35.301048, 'lon' => 138.877573),
		'1817' => array( 'name' => 'Fukuroi', 'lat' => 34.7500522, 'lon' => 137.9258871),
		'1818' => array( 'name' => 'Tenryu', 'lat' => 35.2761975, 'lon' => 137.8543424),
		'1819' => array( 'name' => 'Hamakita', 'lat' => 36.7324457, 'lon' => 136.6957647),
		'1820' => array( 'name' => 'Shimoda', 'lat' => 34.679545, 'lon' => 138.945379),
		'1821' => array( 'name' => 'Susono', 'lat' => 35.21843, 'lon' => 138.881738),
		'1822' => array( 'name' => 'Kosai', 'lat' => 34.710542, 'lon' => 137.531599),
		'1823' => array( 'name' => 'Izu', 'lat' => 34.9764551, 'lon' => 138.9467078),
		'1824' => array( 'name' => 'Omaezaki', 'lat' => 34.647956, 'lon' => 138.146936),
		'1825' => array( 'name' => 'Kikugawa', 'lat' => 34.7562641, 'lon' => 138.0873547),
		'1826' => array( 'name' => 'Izunokuni', 'lat' => 35.035531, 'lon' => 138.961915),
		'1827' => array( 'name' => 'Makinohara', 'lat' => 34.718766, 'lon' => 138.18517),
		'1901' => array( 'name' => 'Gifu', 'lat' => 35.7867449, 'lon' => 137.0460777),
		'1902' => array( 'name' => 'Ogaki', 'lat' => 35.3671141, 'lon' => 136.6179746),
		'1903' => array( 'name' => 'Takayama', 'lat' => 36.1396246, 'lon' => 137.2510322),
		'1904' => array( 'name' => 'Tajimi', 'lat' => 35.3329961, 'lon' => 137.1319459),
		'1905' => array( 'name' => 'Seki', 'lat' => 35.4958031, 'lon' => 136.9181482),
		'1906' => array( 'name' => 'Nakatsugawa', 'lat' => 35.4876463, 'lon' => 137.5005402),
		'1907' => array( 'name' => 'Mino', 'lat' => 35.5442619, 'lon' => 136.9075182),
		'1908' => array( 'name' => 'Mizunami', 'lat' => 35.3619929, 'lon' => 137.2541668),
		'1909' => array( 'name' => 'Hashima', 'lat' => 35.3195107, 'lon' => 136.7027348),
		'1910' => array( 'name' => 'Ena', 'lat' => 35.4492674, 'lon' => 137.412703),
		'1911' => array( 'name' => 'Minokamo', 'lat' => 35.4406551, 'lon' => 137.0155052),
		'1912' => array( 'name' => 'Toki', 'lat' => 35.3524854, 'lon' => 137.1834191),
		'1913' => array( 'name' => 'Kakamigahara', 'lat' => 35.3995831, 'lon' => 136.8485648),
		'1914' => array( 'name' => 'Kani', 'lat' => 35.4261093, 'lon' => 137.0613166),
		'1915' => array( 'name' => 'Yamagata', 'lat' => 38.4746705, 'lon' => 140.083237),
		'1916' => array( 'name' => 'Mizuho', 'lat' => 35.7487675, 'lon' => 139.7015731),
		'1917' => array( 'name' => 'Hida', 'lat' => 36.2383204, 'lon' => 137.1859372),
		'1918' => array( 'name' => 'Motosu', 'lat' => 35.4830261, 'lon' => 136.6780554),
		'1919' => array( 'name' => 'Gujo', 'lat' => 35.748417, 'lon' => 136.9643095),
		'1920' => array( 'name' => 'Gero', 'lat' => 35.8064271, 'lon' => 137.2433187),
		'1921' => array( 'name' => 'Kaizu', 'lat' => 35.2205087, 'lon' => 136.637211),
		'2001' => array( 'name' => 'Nagoya', 'lat' => 35.1851045, 'lon' => 136.8998438),
		'2002' => array( 'name' => 'Toyohashi', 'lat' => 34.769123, 'lon' => 137.391461),
		'2003' => array( 'name' => 'Okazaki', 'lat' => 34.950974, 'lon' => 137.260842),
		'2004' => array( 'name' => 'Ichinomiya', 'lat' => 35.304878, 'lon' => 136.806915),
		'2005' => array( 'name' => 'Seto', 'lat' => 35.240984, 'lon' => 137.116187),
		'2006' => array( 'name' => 'Handa', 'lat' => 34.8938136, 'lon' => 136.9369523),
		'2007' => array( 'name' => 'Kasugai', 'lat' => 35.273804, 'lon' => 137.007459),
		'2008' => array( 'name' => 'Toyokawa', 'lat' => 34.827644, 'lon' => 137.378586),
		'2009' => array( 'name' => 'Tsushima', 'lat' => 34.3952765, 'lon' => 129.315449),
		'2010' => array( 'name' => 'Hekinan', 'lat' => 34.86505, 'lon' => 136.984523),
		'2011' => array( 'name' => 'Kariya', 'lat' => 34.974678, 'lon' => 137.002791),
		'2012' => array( 'name' => 'Toyota', 'lat' => 35.09611, 'lon' => 137.15631),
		'2013' => array( 'name' => 'Anjo', 'lat' => 34.947764, 'lon' => 137.075563),
		'2014' => array( 'name' => 'Nishio', 'lat' => 34.822498, 'lon' => 137.069626),
		'2015' => array( 'name' => 'Gamagori', 'lat' => 34.833929, 'lon' => 137.225163),
		'2016' => array( 'name' => 'Inuyama', 'lat' => 35.3609734, 'lon' => 136.9845491),
		'2017' => array( 'name' => 'Tokoname', 'lat' => 34.895858, 'lon' => 136.838323),
		'2018' => array( 'name' => 'Moriyama', 'lat' => 35.0513649, 'lon' => 135.9958308),
		'2019' => array( 'name' => 'Konan', 'lat' => 35.344508, 'lon' => 136.866742),
		'2020' => array( 'name' => 'Bisai', 'lat' => 35.3176054, 'lon' => 136.7781385),
		'2021' => array( 'name' => 'Komaki', 'lat' => 35.3066073, 'lon' => 136.934098),
		'2022' => array( 'name' => 'Inazawa', 'lat' => 35.240484, 'lon' => 136.758964),
		'2023' => array( 'name' => 'Shinshiro', 'lat' => 34.946784, 'lon' => 137.533234),
		'2024' => array( 'name' => 'Tokai', 'lat' => 36.46075, 'lon' => 140.58052),
		'2025' => array( 'name' => 'Obu', 'lat' => 35.0165825, 'lon' => 136.954545),
		'2026' => array( 'name' => 'Chita', 'lat' => 34.96956, 'lon' => 136.86400),
		'2027' => array( 'name' => 'Takahama', 'lat' => 34.920796, 'lon' => 136.988604),
		'2028' => array( 'name' => 'Chiryu', 'lat' => 35.0014831, 'lon' => 137.0516484),
		'2029' => array( 'name' => 'Owariasahi', 'lat' => 35.208148, 'lon' => 137.03722),
		'2030' => array( 'name' => 'Iwakura', 'lat' => 35.274632, 'lon' => 136.870713),
		'2031' => array( 'name' => 'Toyoake', 'lat' => 35.05936, 'lon' => 137.013714),
		'2032' => array( 'name' => 'Nissin', 'lat' => 35.6559905, 'lon' => 139.7400983),
		'2033' => array( 'name' => 'Tahara', 'lat' => 34.639112, 'lon' => 137.183207),
		'2034' => array( 'name' => 'Aisai', 'lat' => 35.142205, 'lon' => 136.727208),
		'2035' => array( 'name' => 'Kiyosu', 'lat' => 35.21595, 'lon' => 136.845107),
		'2036' => array( 'name' => 'Kitanagoya', 'lat' => 35.244369, 'lon' => 136.879032),
		'2037' => array( 'name' => 'Yatomi', 'lat' => 35.098209, 'lon' => 136.747831),
		'2038' => array( 'name' => 'Miyoshi', 'lat' => 34.81024, 'lon' => 132.851691),
		'2039' => array( 'name' => 'Ama', 'lat' => 36.08498, 'lon' => 133.10022),
		'2040' => array( 'name' => 'Nagakute', 'lat' => 35.174318, 'lon' => 137.070047),
		'2101' => array( 'name' => 'Tsu', 'lat' => 34.7341973, 'lon' => 136.5153283),
		'2102' => array( 'name' => 'Yokkaichi', 'lat' => 34.9648428, 'lon' => 136.624845),
		'2103' => array( 'name' => 'Ise', 'lat' => 34.4996115, 'lon' => 136.7271774),
		'2104' => array( 'name' => 'Matsusaka', 'lat' => 34.5868422, 'lon' => 136.5412491),
		'2105' => array( 'name' => 'Kuwana', 'lat' => 35.0666099, 'lon' => 136.6843004),
		'2106' => array( 'name' => 'Ueno', 'lat' => 35.7117224, 'lon' => 139.776143),
		'2107' => array( 'name' => 'Suzuka', 'lat' => 34.8817102, 'lon' => 136.5836516),
		'2108' => array( 'name' => 'Nabari', 'lat' => 34.6279243, 'lon' => 136.1086582),
		'2109' => array( 'name' => 'Owase', 'lat' => 34.0707815, 'lon' => 136.1911394),
		'2110' => array( 'name' => 'Kameyama', 'lat' => 34.8619883, 'lon' => 136.446694),
		'2111' => array( 'name' => 'Toba', 'lat' => 34.4714464, 'lon' => 136.8293576),
		'2112' => array( 'name' => 'Kumano', 'lat' => 33.8885409, 'lon' => 136.100411),
		'2113' => array( 'name' => 'Hisai', 'lat' => 34.6758168, 'lon' => 136.4779166),
		'2114' => array( 'name' => 'Ujiyamada', 'lat' => 34.48825, 'lon' => 136.7139235),
		'2115' => array( 'name' => 'Inabe', 'lat' => 35.1573562, 'lon' => 136.5043894),
		'2116' => array( 'name' => 'Shima', 'lat' => 34.3411841, 'lon' => 136.8196451),
		'2117' => array( 'name' => 'Iga', 'lat' => 34.7497761, 'lon' => 136.1423355),
		'2201' => array( 'name' => 'Kyoto', 'lat' => 35.021041, 'lon' => 135.7556075),
		'2202' => array( 'name' => 'Fukuchiyama', 'lat' => 35.2966996, 'lon' => 135.126643),
		'2203' => array( 'name' => 'Maiduru', 'lat' => 35.46859, 'lon' => 135.33964),
		'2204' => array( 'name' => 'Ayabe', 'lat' => 35.359169, 'lon' => 135.353791),
		'2205' => array( 'name' => 'Uji', 'lat' => 34.8933744, 'lon' => 135.8059219),
		'2206' => array( 'name' => 'Miyazu', 'lat' => 35.5815605, 'lon' => 135.199274),
		'2207' => array( 'name' => 'Kameoka', 'lat' => 35.0134403, 'lon' => 135.5733728),
		'2208' => array( 'name' => 'Joyo', 'lat' => 34.8457965, 'lon' => 135.798831),
		'2209' => array( 'name' => 'Nagaokakyo', 'lat' => 34.924418, 'lon' => 135.680271),
		'2210' => array( 'name' => 'Muko', 'lat' => 34.9492795, 'lon' => 135.700666),
		'2211' => array( 'name' => 'Yawata', 'lat' => 34.860714, 'lon' => 135.7164),
		'2212' => array( 'name' => 'Kyotanabe', 'lat' => 34.8029175, 'lon' => 135.760651),
		'2213' => array( 'name' => 'Kyotango', 'lat' => 35.6455135, 'lon' => 135.0434555),
		'2214' => array( 'name' => 'Nantan', 'lat' => 35.2027706, 'lon' => 135.51952),
		'2215' => array( 'name' => 'Kizugawa', 'lat' => 34.75455, 'lon' => 135.848026),
		'2301' => array( 'name' => 'Otsu', 'lat' => 35.0047096, 'lon' => 135.8686739),
		'2302' => array( 'name' => 'Hikone', 'lat' => 35.254276, 'lon' => 136.215376),
		'2303' => array( 'name' => 'Nagahama', 'lat' => 35.4857724, 'lon' => 136.294057),
		'2304' => array( 'name' => 'Omihachiman', 'lat' => 35.1527915, 'lon' => 136.0687365),
		'2305' => array( 'name' => 'Yokaichi', 'lat' => 35.1138501, 'lon' => 136.2036693),
		'2306' => array( 'name' => 'Kusatsu', 'lat' => 35.0179088, 'lon' => 135.96046),
		'2307' => array( 'name' => 'Moriyama', 'lat' => 35.0513649, 'lon' => 135.9958308),
		'2308' => array( 'name' => 'Ritto', 'lat' => 35.0257669, 'lon' => 135.9836707),
		'2309' => array( 'name' => 'Koka', 'lat' => 34.9625992, 'lon' => 136.1655415),
		'2310' => array( 'name' => 'Yasu', 'lat' => 35.0932388, 'lon' => 136.0159278),
		'2311' => array( 'name' => 'Konan', 'lat' => 35.344508, 'lon' => 136.866742),
		'2312' => array( 'name' => 'Takashima', 'lat' => 35.3519223, 'lon' => 136.0423359),
		'2313' => array( 'name' => 'Higashioumi', 'lat' => 35.10936, 'lon' => 136.28598),
		'2314' => array( 'name' => 'Maibara', 'lat' => 35.3149074, 'lon' => 136.2901179),
		'2401' => array( 'name' => 'Nara', 'lat' => 34.2963089, 'lon' => 135.8816819),
		'2402' => array( 'name' => 'Yamatotakada', 'lat' => 34.5150901, 'lon' => 135.7364961),
		'2403' => array( 'name' => 'Yamatokoriyama', 'lat' => 34.6496181, 'lon' => 135.7829497),
		'2404' => array( 'name' => 'Tenri', 'lat' => 34.5965738, 'lon' => 135.8373696),
		'2405' => array( 'name' => 'Kashihara', 'lat' => 34.5094067, 'lon' => 135.792955),
		'2406' => array( 'name' => 'Sakurai', 'lat' => 34.5186316, 'lon' => 135.8434724),
		'2407' => array( 'name' => 'Gojo', 'lat' => 34.351939, 'lon' => 135.693596),
		'2408' => array( 'name' => 'Gose', 'lat' => 34.4651956, 'lon' => 135.7338458),
		'2409' => array( 'name' => 'Ikoma', 'lat' => 34.6915267, 'lon' => 135.6961681),
		'2410' => array( 'name' => 'Kashiba', 'lat' => 34.5415175, 'lon' => 135.6988328),
		'2411' => array( 'name' => 'Katsuragi', 'lat' => 34.4888006, 'lon' => 135.7266816),
		'2412' => array( 'name' => 'Uda', 'lat' => 34.52121, 'lon' => 135.99972),
		'2501' => array( 'name' => 'Osaka', 'lat' => 34.7021912, 'lon' => 135.4955866),
		'2502' => array( 'name' => 'Sakai', 'lat' => 34.5289178, 'lon' => 135.5015548),
		'2503' => array( 'name' => 'Kishiwada', 'lat' => 34.4644, 'lon' => 135.385237),
		'2504' => array( 'name' => 'Toyonaka', 'lat' => 34.7862025, 'lon' => 135.4737093),
		'2505' => array( 'name' => 'Fuse', 'lat' => 34.6640039, 'lon' => 135.563573),
		'2506' => array( 'name' => 'Ikeda', 'lat' => 34.819371, 'lon' => 135.433332),
		'2507' => array( 'name' => 'Suita', 'lat' => 34.764884, 'lon' => 135.51735),
		'2508' => array( 'name' => 'Izumiotsu', 'lat' => 34.506612, 'lon' => 135.408793),
		'2509' => array( 'name' => 'Takatsuki', 'lat' => 34.8812905, 'lon' => 135.6012398),
		'2510' => array( 'name' => 'Kaiduka', 'lat' => 35.6256085, 'lon' => 140.1396283),
		'2511' => array( 'name' => 'Moriguchi', 'lat' => 34.746898, 'lon' => 135.566663),
		'2512' => array( 'name' => 'Hirakata', 'lat' => 34.818215, 'lon' => 135.659225),
		'2513' => array( 'name' => 'Ibaraki', 'lat' => 36.2869536, 'lon' => 140.4703384),
		'2514' => array( 'name' => 'Yao', 'lat' => 34.626275, 'lon' => 135.605845),
		'2515' => array( 'name' => 'Izumisano', 'lat' => 34.394629, 'lon' => 135.322725),
		'2516' => array( 'name' => 'Tondabayashi', 'lat' => 34.478781, 'lon' => 135.595519),
		'2517' => array( 'name' => 'Neyagawa', 'lat' => 34.76751, 'lon' => 135.633907),
		'2518' => array( 'name' => 'Kawachinagano', 'lat' => 34.4575979, 'lon' => 135.5643131),
		'2519' => array( 'name' => 'Hiraoka', 'lat' => 35.273846, 'lon' => 137.8538052),
		'2520' => array( 'name' => 'Kawachi', 'lat' => 35.8845477, 'lon' => 140.2446262),
		'2521' => array( 'name' => 'Matsubara', 'lat' => 34.580779, 'lon' => 135.546552),
		'2522' => array( 'name' => 'Daito', 'lat' => 34.710679, 'lon' => 135.635478),
		'2523' => array( 'name' => 'Izumi', 'lat' => 34.43108, 'lon' => 135.474789),
		'2524' => array( 'name' => 'Mino', 'lat' => 35.5442619, 'lon' => 136.9075182),
		'2525' => array( 'name' => 'Kashiwara', 'lat' => 34.59077, 'lon' => 135.635035),
		'2526' => array( 'name' => 'Habikino', 'lat' => 34.54158, 'lon' => 135.599097),
		'2527' => array( 'name' => 'Kadoma', 'lat' => 34.728801, 'lon' => 135.59691),
		'2528' => array( 'name' => 'Settsu', 'lat' => 34.782761, 'lon' => 135.553584),
		'2529' => array( 'name' => 'Fujiidera', 'lat' => 34.5715838, 'lon' => 135.594395),
		'2530' => array( 'name' => 'Takaishi', 'lat' => 34.532059, 'lon' => 135.424388),
		'2531' => array( 'name' => 'Higashiosaka', 'lat' => 34.678147, 'lon' => 135.597728),
		'2532' => array( 'name' => 'Sennan', 'lat' => 34.3657277, 'lon' => 135.2739742),
		'2533' => array( 'name' => 'Shijonawate', 'lat' => 34.730447, 'lon' => 135.674005),
		'2534' => array( 'name' => 'Katano', 'lat' => 34.792144, 'lon' => 135.678333),
		'2535' => array( 'name' => 'Osakasayama', 'lat' => 34.503879, 'lon' => 135.549966),
		'2536' => array( 'name' => 'Hannan', 'lat' => 34.3595067, 'lon' => 135.2398397),
		'2601' => array( 'name' => 'Wakayama', 'lat' => 33.8070292, 'lon' => 135.5930743),
		'2602' => array( 'name' => 'Shingu', 'lat' => 33.7241003, 'lon' => 135.9930029),
		'2603' => array( 'name' => 'Kainan', 'lat' => 34.1548656, 'lon' => 135.2092791),
		'2604' => array( 'name' => 'Tanabe', 'lat' => 33.7278991, 'lon' => 135.3777917),
		'2605' => array( 'name' => 'Gobo', 'lat' => 33.8913819, 'lon' => 135.152572),
		'2606' => array( 'name' => 'Hashimoto', 'lat' => 34.3258291, 'lon' => 135.6190034),
		'2607' => array( 'name' => 'Arida', 'lat' => 34.0832764, 'lon' => 135.1278229),
		'2608' => array( 'name' => 'Kinokawa', 'lat' => 34.2697102, 'lon' => 135.3636692),
		'2609' => array( 'name' => 'Iwade', 'lat' => 34.2558554, 'lon' => 135.3112418),
		'2701' => array( 'name' => 'Kobe', 'lat' => 34.6932379, 'lon' => 135.1943764),
		'2702' => array( 'name' => 'Himeji', 'lat' => 34.8153529, 'lon' => 134.6854793),
		'2703' => array( 'name' => 'Amagasaki', 'lat' => 34.7288995, 'lon' => 135.412989),
		'2704' => array( 'name' => 'Akashi', 'lat' => 34.6832635, 'lon' => 134.9476925),
		'2705' => array( 'name' => 'Nishinomiya', 'lat' => 34.7386033, 'lon' => 135.3394138),
		'2706' => array( 'name' => 'Sumoto', 'lat' => 34.3389909, 'lon' => 134.859985),
		'2707' => array( 'name' => 'Ashiya', 'lat' => 34.7324313, 'lon' => 135.3069326),
		'2708' => array( 'name' => 'Itami', 'lat' => 34.786159, 'lon' => 135.407994),
		'2709' => array( 'name' => 'Aioi', 'lat' => 34.8097456, 'lon' => 134.4719626),
		'2710' => array( 'name' => 'Toyooka', 'lat' => 35.5446737, 'lon' => 134.8201126),
		'2711' => array( 'name' => 'Kakogawa', 'lat' => 34.786771, 'lon' => 134.8498955),
		'2712' => array( 'name' => 'Tatsuno', 'lat' => 34.8743067, 'lon' => 134.512583),
		'2713' => array( 'name' => 'Ako', 'lat' => 34.78986, 'lon' => 134.37209),
		'2714' => array( 'name' => 'Nishiwaki', 'lat' => 35.0125755, 'lon' => 134.996262),
		'2715' => array( 'name' => 'Takaraduka', 'lat' => 34.8036525, 'lon' => 135.3673535),
		'2716' => array( 'name' => 'Miki', 'lat' => 34.8533422, 'lon' => 135.0765718),
		'2717' => array( 'name' => 'Takasago', 'lat' => 34.7739854, 'lon' => 134.7880412),
		'2718' => array( 'name' => 'Kawanishi', 'lat' => 34.8699135, 'lon' => 135.4147005),
		'2719' => array( 'name' => 'Ono', 'lat' => 34.861365, 'lon' => 134.9547785),
		'2720' => array( 'name' => 'Sanda', 'lat' => 34.8889881, 'lon' => 135.2284629),
		'2721' => array( 'name' => 'Kasai', 'lat' => 34.92507, 'lon' => 134.85322),
		'2722' => array( 'name' => 'Sasayama', 'lat' => 33.0558032, 'lon' => 132.6590785),
		'2723' => array( 'name' => 'Yabu', 'lat' => 35.4044647, 'lon' => 134.7674637),
		'2724' => array( 'name' => 'Tanba', 'lat' => 35.17494, 'lon' => 135.04373),
		'2725' => array( 'name' => 'Minamiawaji', 'lat' => 34.2556695, 'lon' => 134.762265),
		'2726' => array( 'name' => 'Asago', 'lat' => 35.3398402, 'lon' => 134.8527042),
		'2727' => array( 'name' => 'Awaji', 'lat' => 34.5014505, 'lon' => 134.9071385),
		'2728' => array( 'name' => 'Shiso', 'lat' => 35.0043515, 'lon' => 134.5495517),
		'2729' => array( 'name' => 'Kato', 'lat' => 34.930544, 'lon' => 135.007803),
		'2730' => array( 'name' => 'Tatsuno', 'lat' => 34.8743067, 'lon' => 134.512583),
		'2731' => array( 'name' => 'Tanbasasayama', 'lat' => 35.075757, 'lon' => 135.2193373),
		'2801' => array( 'name' => 'Toyama', 'lat' => 36.6468015, 'lon' => 137.2183531),
		'2802' => array( 'name' => 'Takaoka', 'lat' => 36.7362147, 'lon' => 137.0187306),
		'2803' => array( 'name' => 'Shinminato', 'lat' => 36.7825348, 'lon' => 137.0794368),
		'2804' => array( 'name' => 'Uodu', 'lat' => 43.086459, 'lon' => 141.4056708),
		'2805' => array( 'name' => 'Himi', 'lat' => 36.8645966, 'lon' => 136.9704027),
		'2806' => array( 'name' => 'Namerikawa', 'lat' => 36.7643429, 'lon' => 137.3413791),
		'2807' => array( 'name' => 'Kurobe', 'lat' => 36.8712828, 'lon' => 137.4478857),
		'2808' => array( 'name' => 'Tonami', 'lat' => 36.6364136, 'lon' => 136.9468199),
		'2809' => array( 'name' => 'Oyabe', 'lat' => 36.6755895, 'lon' => 136.8688637),
		'2810' => array( 'name' => 'Nanto', 'lat' => 36.45356, 'lon' => 136.91942),
		'2811' => array( 'name' => 'Imizu', 'lat' => 36.7304657, 'lon' => 137.0753818),
		'2901' => array( 'name' => 'Fukui', 'lat' => 35.9263502, 'lon' => 136.6068127),
		'2902' => array( 'name' => 'Tsuruga', 'lat' => 35.6445135, 'lon' => 136.0734634),
		'2903' => array( 'name' => 'Takefu', 'lat' => 35.9031163, 'lon' => 136.1710672),
		'2904' => array( 'name' => 'Obama', 'lat' => 35.4938281, 'lon' => 135.7446614),
		'2905' => array( 'name' => 'Ono', 'lat' => 34.861365, 'lon' => 134.9547785),
		'2906' => array( 'name' => 'Katsuyama', 'lat' => 36.060766, 'lon' => 136.5007964),
		'2907' => array( 'name' => 'Sabae', 'lat' => 35.9565096, 'lon' => 136.1843593),
		'2908' => array( 'name' => 'Awara', 'lat' => 36.2113447, 'lon' => 136.2290431),
		'2909' => array( 'name' => 'Echizen', 'lat' => 35.9034571, 'lon' => 136.1689317),
		'2910' => array( 'name' => 'Sakai', 'lat' => 34.5289178, 'lon' => 135.5015548),
		'3001' => array( 'name' => 'Kanazawa', 'lat' => 36.5780499, 'lon' => 136.6480247),
		'3002' => array( 'name' => 'Nanao', 'lat' => 37.0521078, 'lon' => 136.946461),
		'3003' => array( 'name' => 'Komatsu', 'lat' => 36.4032931, 'lon' => 136.4495465),
		'3004' => array( 'name' => 'Wajima', 'lat' => 37.3905644, 'lon' => 136.8994281),
		'3005' => array( 'name' => 'Suzu', 'lat' => 37.438147, 'lon' => 137.2484135),
		'3006' => array( 'name' => 'Kaga', 'lat' => 36.25406, 'lon' => 136.37874),
		'3007' => array( 'name' => 'Hakui', 'lat' => 36.8948225, 'lon' => 136.7782938),
		'3008' => array( 'name' => 'Matsuto', 'lat' => 36.51667, 'lon' => 136.56667),
		'3009' => array( 'name' => 'Kahoku', 'lat' => 36.7498188, 'lon' => 136.7195952),
		'3010' => array( 'name' => 'Hakusan', 'lat' => 35.7215048, 'lon' => 139.7521578),
		'3011' => array( 'name' => 'Nomi', 'lat' => 36.43655, 'lon' => 136.54449),
		'3012' => array( 'name' => 'Nonoichi', 'lat' => 36.5197206, 'lon' => 136.6098107),
		'3101' => array( 'name' => 'Okayama', 'lat' => 34.8581334, 'lon' => 133.7759256),
		'3102' => array( 'name' => 'Kurashiki', 'lat' => 34.5850791, 'lon' => 133.7719957),
		'3103' => array( 'name' => 'Tsuyama', 'lat' => 35.0691284, 'lon' => 134.0043355),
		'3104' => array( 'name' => 'Tamano', 'lat' => 34.491942, 'lon' => 133.9460028),
		'3105' => array( 'name' => 'Kojima', 'lat' => 34.4627408, 'lon' => 133.8076652),
		'3106' => array( 'name' => 'Tamashima', 'lat' => 34.5527958, 'lon' => 133.6849722),
		'3107' => array( 'name' => 'Kasaoka', 'lat' => 34.5071461, 'lon' => 133.5074654),
		'3108' => array( 'name' => 'saidaiji', 'lat' => 34.6619241, 'lon' => 134.0373128),
		'3109' => array( 'name' => 'Ibara', 'lat' => 34.5977243, 'lon' => 133.4638119),
		'3110' => array( 'name' => 'Soja', 'lat' => 34.6728162, 'lon' => 133.7466763),
		'3111' => array( 'name' => 'Takahashi', 'lat' => 34.7908975, 'lon' => 133.6169111),
		'3112' => array( 'name' => 'Niimi', 'lat' => 34.9775653, 'lon' => 133.4704309),
		'3113' => array( 'name' => 'Bizen', 'lat' => 34.744987, 'lon' => 134.1882633),
		'3114' => array( 'name' => 'Setouchi', 'lat' => 34.6647608, 'lon' => 134.0926948),
		'3115' => array( 'name' => 'Akaiwa', 'lat' => 34.7553412, 'lon' => 134.0188207),
		'3116' => array( 'name' => 'Maniwa', 'lat' => 35.075681, 'lon' => 133.7532375),
		'3117' => array( 'name' => 'Mimasaka', 'lat' => 35.0085854, 'lon' => 134.1485964),
		'3118' => array( 'name' => 'Asakuchi', 'lat' => 34.5279193, 'lon' => 133.5849605),
		'3201' => array( 'name' => 'Matsue', 'lat' => 35.468115, 'lon' => 133.048768),
		'3202' => array( 'name' => 'Hamada', 'lat' => 34.8991982, 'lon' => 132.0799984),
		'3203' => array( 'name' => 'Izumo', 'lat' => 35.3668891, 'lon' => 132.7548827),
		'3204' => array( 'name' => 'Masuda', 'lat' => 34.6748584, 'lon' => 131.8428933),
		'3205' => array( 'name' => 'Oda', 'lat' => 35.1920988, 'lon' => 132.4994679),
		'3206' => array( 'name' => 'Yasugi', 'lat' => 35.431337, 'lon' => 133.250942),
		'3207' => array( 'name' => 'Gotsu', 'lat' => 35.0111168, 'lon' => 132.2213266),
		'3208' => array( 'name' => 'Hirata', 'lat' => 37.2219658, 'lon' => 140.5756791),
		'3209' => array( 'name' => 'Unnan', 'lat' => 35.2880963, 'lon' => 132.9001494),
		'3301' => array( 'name' => 'Yamaguchi', 'lat' => 34.2379614, 'lon' => 131.5873845),
		'3302' => array( 'name' => 'Shimonoseki', 'lat' => 33.9577116, 'lon' => 130.9415455),
		'3303' => array( 'name' => 'Ube', 'lat' => 33.9518498, 'lon' => 131.2472243),
		'3304' => array( 'name' => 'Hagi', 'lat' => 34.4074815, 'lon' => 131.399194),
		'3305' => array( 'name' => 'Tokuyama', 'lat' => 34.0510183, 'lon' => 131.8020941),
		'3306' => array( 'name' => 'Hofu', 'lat' => 34.0517226, 'lon' => 131.5629141),
		'3307' => array( 'name' => 'Kudamatsu', 'lat' => 34.0149872, 'lon' => 131.8704567),
		'3308' => array( 'name' => 'Iwakuni', 'lat' => 34.1664995, 'lon' => 132.2191163),
		'3309' => array( 'name' => 'Onoda', 'lat' => 34.0079811, 'lon' => 131.1854093),
		'3310' => array( 'name' => 'Hikari', 'lat' => 33.9615807, 'lon' => 131.9425203),
		'3311' => array( 'name' => 'Nagato', 'lat' => 34.3708941, 'lon' => 131.1821587),
		'3312' => array( 'name' => 'Yanai', 'lat' => 33.9640825, 'lon' => 132.101193),
		'3313' => array( 'name' => 'Mine', 'lat' => 34.1666165, 'lon' => 131.2054466),
		'3314' => array( 'name' => 'Shinnan\'yo', 'lat' => 34.0696603, 'lon' => 131.7700182),
		'3315' => array( 'name' => 'Shunan', 'lat' => 34.0550595, 'lon' => 131.8064092),
		'3316' => array( 'name' => 'San\'yoonoda', 'lat' => 34.04390, 'lon' => 131.16032),
		'3401' => array( 'name' => 'Tottori', 'lat' => 35.3555075, 'lon' => 133.8678525),
		'3402' => array( 'name' => 'Kurayoshi', 'lat' => 35.430166, 'lon' => 133.825525),
		'3403' => array( 'name' => 'Yonago', 'lat' => 35.4276408, 'lon' => 133.331459),
		'3404' => array( 'name' => 'Sakaiminato', 'lat' => 35.5391751, 'lon' => 133.2318575),
		'3501' => array( 'name' => 'Hiroshima', 'lat' => 34.3917241, 'lon' => 132.4517589),
		'3502' => array( 'name' => 'Kure', 'lat' => 34.2484488, 'lon' => 132.5652498),
		'3503' => array( 'name' => 'Takehara', 'lat' => 34.3418377, 'lon' => 132.9070476),
		'3504' => array( 'name' => 'Mihara', 'lat' => 34.3974407, 'lon' => 133.0785046),
		'3505' => array( 'name' => 'Onomichi', 'lat' => 34.4088519, 'lon' => 133.2051549),
		'3506' => array( 'name' => 'Innoshima', 'lat' => 34.3388148, 'lon' => 133.1610464),
		'3507' => array( 'name' => 'Matsunaga', 'lat' => 34.4507804, 'lon' => 133.2586195),
		'3508' => array( 'name' => 'Fukuyama', 'lat' => 34.4857039, 'lon' => 133.3623097),
		'3509' => array( 'name' => 'Fuchu', 'lat' => 34.5683141, 'lon' => 133.2366327),
		'3510' => array( 'name' => 'Miyoshi', 'lat' => 34.81024, 'lon' => 132.851691),
		'3511' => array( 'name' => 'Syoubara', 'lat' => 34.8272451, 'lon' => 132.9753076),
		'3512' => array( 'name' => 'Otake', 'lat' => 34.2378742, 'lon' => 132.2223092),
		'3513' => array( 'name' => 'Higashihiroshima', 'lat' => 34.42683, 'lon' => 132.741552),
		'3514' => array( 'name' => 'Hatsukaichi', 'lat' => 34.3485048, 'lon' => 132.331833),
		'3515' => array( 'name' => 'Akitakata', 'lat' => 34.70296, 'lon' => 132.6775),
		'3516' => array( 'name' => 'Etajima', 'lat' => 34.1749619, 'lon' => 132.4622276),
		'3601' => array( 'name' => 'Takamatsu', 'lat' => 34.3425592, 'lon' => 134.0465338),
		'3602' => array( 'name' => 'Marugame', 'lat' => 34.2888128, 'lon' => 133.7982421),
		'3603' => array( 'name' => 'Sakaide', 'lat' => 34.3082086, 'lon' => 133.8698532),
		'3604' => array( 'name' => 'Zentsuji', 'lat' => 34.2194913, 'lon' => 133.7613603),
		'3605' => array( 'name' => 'Kan\'onji', 'lat' => 34.1284693, 'lon' => 133.6628679),
		'3606' => array( 'name' => 'Sanuki', 'lat' => 34.2931892, 'lon' => 134.1890881),
		'3607' => array( 'name' => 'Higashikagawa', 'lat' => 34.2223617, 'lon' => 134.3192814),
		'3608' => array( 'name' => 'Mitoyo', 'lat' => 34.1986856, 'lon' => 133.7179334),
		'3701' => array( 'name' => 'Tokushima', 'lat' => 33.9196418, 'lon' => 134.2509634),
		'3702' => array( 'name' => 'Naruto', 'lat' => 35.6084534, 'lon' => 140.4108142),
		'3703' => array( 'name' => 'Komatsushima', 'lat' => 34.0044235, 'lon' => 134.5906577),
		'3704' => array( 'name' => 'Anan', 'lat' => 35.31854, 'lon' => 137.76495),
		'3705' => array( 'name' => 'Yoshinogawa', 'lat' => 34.0663158, 'lon' => 134.3585119),
		'3706' => array( 'name' => 'Awa', 'lat' => 35.1254245, 'lon' => 139.8358817),
		'3707' => array( 'name' => 'Mima', 'lat' => 34.0537915, 'lon' => 134.1700644),
		'3708' => array( 'name' => 'Miyoshi', 'lat' => 34.81024, 'lon' => 132.851691),
		'3801' => array( 'name' => 'Matsuyama', 'lat' => 33.8395188, 'lon' => 132.7653521),
		'3802' => array( 'name' => 'Imabari', 'lat' => 34.0658182, 'lon' => 132.9976758),
		'3803' => array( 'name' => 'Uwajima', 'lat' => 33.2232315, 'lon' => 132.5606514),
		'3804' => array( 'name' => 'Yawatahama', 'lat' => 33.4627983, 'lon' => 132.4235208),
		'3805' => array( 'name' => 'Niihama', 'lat' => 33.9603497, 'lon' => 133.2835899),
		'3806' => array( 'name' => 'Saijo', 'lat' => 33.9194466, 'lon' => 133.1813268),
		'3807' => array( 'name' => 'Ozu', 'lat' => 33.506488, 'lon' => 132.5446842),
		'3808' => array( 'name' => 'Iyomishima', 'lat' => 33.9, 'lon' => 133.5),
		'3809' => array( 'name' => 'Kawanoe', 'lat' => 34.0143555, 'lon' => 133.5760642),
		'3810' => array( 'name' => 'Iyo', 'lat' => 33.7578962, 'lon' => 132.7039458),
		'3811' => array( 'name' => 'Hojo', 'lat' => 34.994923, 'lon' => 139.8665482),
		'3812' => array( 'name' => 'Toyo', 'lat' => 33.5230707, 'lon' => 134.2429417),
		'3813' => array( 'name' => 'Shikokuchuo', 'lat' => 33.980744, 'lon' => 133.5499338),
		'3814' => array( 'name' => 'Seiyo', 'lat' => 33.3625533, 'lon' => 132.5109394),
		'3815' => array( 'name' => 'Toon', 'lat' => 33.7908821, 'lon' => 132.8718965),
		'3901' => array( 'name' => 'Kochi', 'lat' => 33.57984, 'lon' => 133.50752),
		'3902' => array( 'name' => 'Muroto', 'lat' => 33.2898523, 'lon' => 134.1522806),
		'3903' => array( 'name' => 'Aki', 'lat' => 33.5019095, 'lon' => 133.9072127),
		'3904' => array( 'name' => 'Tosa', 'lat' => 33.4960168, 'lon' => 133.425544),
		'3905' => array( 'name' => 'Susaki', 'lat' => 33.400826, 'lon' => 133.2829594),
		'3906' => array( 'name' => 'Nakamura', 'lat' => 32.9843748, 'lon' => 132.9440278),
		'3907' => array( 'name' => 'Sukumo', 'lat' => 32.9390595, 'lon' => 132.7262671),
		'3908' => array( 'name' => 'Tosashimizu', 'lat' => 32.7816213, 'lon' => 132.9548557),
		'3909' => array( 'name' => 'Nankoku', 'lat' => 33.5755463, 'lon' => 133.6413009),
		'3910' => array( 'name' => 'Shimanto', 'lat' => 32.9912232, 'lon' => 132.9336984),
		'3911' => array( 'name' => 'Konan', 'lat' => 35.344508, 'lon' => 136.866742),
		'3912' => array( 'name' => 'Kami', 'lat' => 35.6321694, 'lon' => 134.6293314),
		'4001' => array( 'name' => 'Fukuoka', 'lat' => 33.6251241, 'lon' => 130.6180016),
		'4002' => array( 'name' => 'Kokura', 'lat' => 33.8867625, 'lon' => 130.8821624),
		'4003' => array( 'name' => 'Moji', 'lat' => 33.9043043, 'lon' => 130.9328578),
		'4004' => array( 'name' => 'Yahata', 'lat' => 33.8692953, 'lon' => 130.7954174),
		'4005' => array( 'name' => 'Tobata', 'lat' => 33.8972177, 'lon' => 130.8206112),
		'4006' => array( 'name' => 'Wakamatsu', 'lat' => 33.9009962, 'lon' => 130.8060685),
		'4007' => array( 'name' => 'Kurume', 'lat' => 33.3196545, 'lon' => 130.5080625),
		'4008' => array( 'name' => 'Omuta', 'lat' => 33.047013, 'lon' => 130.464155),
		'4009' => array( 'name' => 'Noogata', 'lat' => 33.743936, 'lon' => 130.7297462),
		'4010' => array( 'name' => 'Iizuka', 'lat' => 33.646594, 'lon' => 130.6911579),
		'4011' => array( 'name' => 'Tagawa', 'lat' => 33.6387807, 'lon' => 130.8063352),
		'4012' => array( 'name' => 'Yanagawa', 'lat' => 33.1630969, 'lon' => 130.4058091),
		'4013' => array( 'name' => 'Amagi', 'lat' => 27.818004, 'lon' => 128.90815),
		'4014' => array( 'name' => 'Yamada', 'lat' => 34.8056396, 'lon' => 135.5155581),
		'4015' => array( 'name' => 'Yame', 'lat' => 33.2116721, 'lon' => 130.5579706),
		'4016' => array( 'name' => 'Chikugo', 'lat' => 33.2123783, 'lon' => 130.5017727),
		'4017' => array( 'name' => 'Okawa', 'lat' => 33.2061857, 'lon' => 130.3835746),
		'4018' => array( 'name' => 'Yukuhashi', 'lat' => 33.7292049, 'lon' => 130.9831626),
		'4019' => array( 'name' => 'Buzen', 'lat' => 33.6114994, 'lon' => 131.1304409),
		'4020' => array( 'name' => 'Nakama', 'lat' => 33.8164202, 'lon' => 130.7090761),
		'4021' => array( 'name' => 'Kitakyushu', 'lat' => 33.8829996, 'lon' => 130.8749015),
		'4022' => array( 'name' => 'Ogoori', 'lat' => 33.3963946, 'lon' => 130.5554371),
		'4023' => array( 'name' => 'Kasuga', 'lat' => 33.5326446, 'lon' => 130.4713013),
		'4024' => array( 'name' => 'Chikushino', 'lat' => 33.4906026, 'lon' => 130.520329),
		'4025' => array( 'name' => 'Onojo', 'lat' => 33.547399, 'lon' => 130.488786),
		'4026' => array( 'name' => 'Munakata', 'lat' => 33.8055642, 'lon' => 130.5406875),
		'4027' => array( 'name' => 'Dazaifu', 'lat' => 33.5129189, 'lon' => 130.5242217),
		'4028' => array( 'name' => 'Maebaru', 'lat' => 32.99109, 'lon' => 130.605873),
		'4029' => array( 'name' => 'Koga', 'lat' => 36.178025, 'lon' => 139.7553638),
		'4030' => array( 'name' => 'Fukutsu', 'lat' => 33.7668264, 'lon' => 130.4913329),
		'4031' => array( 'name' => 'Ukiha', 'lat' => 33.3473997, 'lon' => 130.7552293),
		'4032' => array( 'name' => 'Miyawaka', 'lat' => 33.7235894, 'lon' => 130.6667511),
		'4033' => array( 'name' => 'Kama', 'lat' => 33.53670, 'lon' => 130.74015),
		'4034' => array( 'name' => 'Asakura', 'lat' => 33.4234248, 'lon' => 130.6657037),
		'4035' => array( 'name' => 'Miyama', 'lat' => 33.1523675, 'lon' => 130.4746267),
		'4036' => array( 'name' => 'Itoshima', 'lat' => 33.5572419, 'lon' => 130.1955242),
		'4037' => array( 'name' => 'Nakagawa', 'lat' => 33.4994449, 'lon' => 130.4216086),
		'4101' => array( 'name' => 'Saga', 'lat' => 33.2185408, 'lon' => 130.1296585),
		'4102' => array( 'name' => 'Karatsu', 'lat' => 33.4503405, 'lon' => 129.9679345),
		'4103' => array( 'name' => 'Tosu', 'lat' => 33.3778536, 'lon' => 130.5061966),
		'4104' => array( 'name' => 'Taku', 'lat' => 33.2885725, 'lon' => 130.1100243),
		'4105' => array( 'name' => 'Imari', 'lat' => 33.2644557, 'lon' => 129.8808439),
		'4106' => array( 'name' => 'Takeo', 'lat' => 33.20099, 'lon' => 129.99846),
		'4107' => array( 'name' => 'Kashima', 'lat' => 35.9661164, 'lon' => 140.6450292),
		'4108' => array( 'name' => 'Ogi', 'lat' => 33.2738076, 'lon' => 130.2171043),
		'4109' => array( 'name' => 'Ureshino', 'lat' => 33.1279109, 'lon' => 130.0599074),
		'4110' => array( 'name' => 'Kanzaki', 'lat' => 33.3115907, 'lon' => 130.3719429),
		'4201' => array( 'name' => 'Nagasaki', 'lat' => 33.1154683, 'lon' => 129.7874339),
		'4202' => array( 'name' => 'Sasebo', 'lat' => 33.1799965, 'lon' => 129.7152872),
		'4203' => array( 'name' => 'Shimabara', 'lat' => 32.788084, 'lon' => 130.3705411),
		'4204' => array( 'name' => 'Isahaya', 'lat' => 32.843426, 'lon' => 130.0530537),
		'4205' => array( 'name' => 'Omura', 'lat' => 32.9002281, 'lon' => 129.9585055),
		'4206' => array( 'name' => 'Fukue', 'lat' => 34.0471135, 'lon' => 130.9156608),
		'4207' => array( 'name' => 'Hirado', 'lat' => 33.3680705, 'lon' => 129.5539153),
		'4208' => array( 'name' => 'Matsuura', 'lat' => 33.3410429, 'lon' => 129.7088042),
		'4209' => array( 'name' => 'Tsushima', 'lat' => 34.3952765, 'lon' => 129.315449),
		'4210' => array( 'name' => 'Iki', 'lat' => 33.7500515, 'lon' => 129.6913078),
		'4211' => array( 'name' => 'Goto', 'lat' => 32.6951424, 'lon' => 128.8408104),
		'4212' => array( 'name' => 'Saikai', 'lat' => 32.9331936, 'lon' => 129.6430585),
		'4213' => array( 'name' => 'Unzen', 'lat' => 32.83515, 'lon' => 130.18772),
		'4214' => array( 'name' => 'Minamishimabara', 'lat' => 32.6597338, 'lon' => 130.2976992),
		'4301' => array( 'name' => 'Kumamoto', 'lat' => 32.6450475, 'lon' => 130.6341345),
		'4302' => array( 'name' => 'Yatsushiro', 'lat' => 32.5081425, 'lon' => 130.6020211),
		'4303' => array( 'name' => 'Hitoyoshi', 'lat' => 32.2056644, 'lon' => 130.7601392),
		'4304' => array( 'name' => 'Arao', 'lat' => 32.9867584, 'lon' => 130.4334027),
		'4305' => array( 'name' => 'Minamata', 'lat' => 32.2123376, 'lon' => 130.4087616),
		'4306' => array( 'name' => 'Tamana', 'lat' => 32.9352591, 'lon' => 130.5628137),
		'4307' => array( 'name' => 'Hondo', 'lat' => 32.45583, 'lon' => 130.17078),
		'4308' => array( 'name' => 'Yamaga', 'lat' => 33.0177456, 'lon' => 130.6911907),
		'4309' => array( 'name' => 'Ushibuka', 'lat' => 32.1939884, 'lon' => 130.0274164),
		'4310' => array( 'name' => 'Kikuchi', 'lat' => 32.9798234, 'lon' => 130.8131987),
		'4311' => array( 'name' => 'Uto', 'lat' => 32.6879177, 'lon' => 130.6598222),
		'4312' => array( 'name' => 'Kamiamakusa', 'lat' => 32.4963015, 'lon' => 130.3960215),
		'4313' => array( 'name' => 'Uki', 'lat' => 32.647181, 'lon' => 130.6839693),
		'4314' => array( 'name' => 'Aso', 'lat' => 32.9524903, 'lon' => 131.1214674),
		'4315' => array( 'name' => 'Amakusa', 'lat' => 32.4585127, 'lon' => 130.1930487),
		'4316' => array( 'name' => 'Koshi', 'lat' => 32.89351, 'lon' => 130.76862),
		'4401' => array( 'name' => 'Oita', 'lat' => 33.2393864, 'lon' => 131.6096524),
		'4402' => array( 'name' => 'Beppu', 'lat' => 33.2845752, 'lon' => 131.4913063),
		'4403' => array( 'name' => 'Nakatsu', 'lat' => 33.5982794, 'lon' => 131.1883225),
		'4404' => array( 'name' => 'Hita', 'lat' => 33.33428, 'lon' => 130.94266),
		'4405' => array( 'name' => 'Saiki', 'lat' => 32.9601732, 'lon' => 131.8996704),
		'4406' => array( 'name' => 'Usuki', 'lat' => 33.1261032, 'lon' => 131.8048454),
		'4407' => array( 'name' => 'Tsukumi', 'lat' => 33.0722942, 'lon' => 131.861347),
		'4408' => array( 'name' => 'Taketa', 'lat' => 32.9736821, 'lon' => 131.3979534),
		'4409' => array( 'name' => 'Tsurusaki', 'lat' => 33.2427592, 'lon' => 131.6869622),
		'4410' => array( 'name' => 'Bungotakada', 'lat' => 33.5562136, 'lon' => 131.4469025),
		'4411' => array( 'name' => 'Kitsuki', 'lat' => 33.416849, 'lon' => 131.6217599),
		'4412' => array( 'name' => 'Usa', 'lat' => 35.01030, 'lon' => 139.07152),
		'4413' => array( 'name' => 'Bungoono', 'lat' => 32.9775643, 'lon' => 131.5841178),
		'4414' => array( 'name' => 'Yufu', 'lat' => 33.1800993, 'lon' => 131.4269323),
		'4415' => array( 'name' => 'Kunisaki', 'lat' => 33.5632982, 'lon' => 131.7322544),
		'4501' => array( 'name' => 'Miyazaki', 'lat' => 32.097681, 'lon' => 131.294542),
		'4502' => array( 'name' => 'Miyakonojo', 'lat' => 31.7196106, 'lon' => 131.0612029),
		'4503' => array( 'name' => 'Nobeoka', 'lat' => 32.5823063, 'lon' => 131.6649034),
		'4504' => array( 'name' => 'Nichinan', 'lat' => 31.6019221, 'lon' => 131.3788769),
		'4505' => array( 'name' => 'Kobayashi', 'lat' => 31.9966841, 'lon' => 130.9731456),
		'4506' => array( 'name' => 'Hyuga', 'lat' => 32.4225483, 'lon' => 131.6244443),
		'4507' => array( 'name' => 'Kushima', 'lat' => 31.4649768, 'lon' => 131.2282568),
		'4508' => array( 'name' => 'Saito', 'lat' => 32.1078882, 'lon' => 131.4008856),
		'4509' => array( 'name' => 'Ebino', 'lat' => 32.0422993, 'lon' => 130.8159272),
		'4601' => array( 'name' => 'Kagoshima', 'lat' => 31.521587, 'lon' => 130.5474077),
		'4602' => array( 'name' => 'Sendai', 'lat' => 38.2677554, 'lon' => 140.8691498),
		'4603' => array( 'name' => 'Kanoya', 'lat' => 31.3780472, 'lon' => 130.8525167),
		'4604' => array( 'name' => 'Makurazaki', 'lat' => 31.2728756, 'lon' => 130.2970739),
		'4605' => array( 'name' => 'Kushikino', 'lat' => 31.7212541, 'lon' => 130.2744046),
		'4606' => array( 'name' => 'Akune', 'lat' => 32.0143139, 'lon' => 130.1927415),
		'4607' => array( 'name' => 'Izumi', 'lat' => 34.43108, 'lon' => 135.474789),
		'4608' => array( 'name' => 'Naze', 'lat' => 26.21094, 'lon' => 127.68359),
		'4609' => array( 'name' => 'Okuchi', 'lat' => 39.9477028, 'lon' => 141.823169),
		'4610' => array( 'name' => 'Ibusuki', 'lat' => 31.2527953, 'lon' => 130.6333097),
		'4611' => array( 'name' => 'Kaseda', 'lat' => 31.41647, 'lon' => 130.31310),
		'4612' => array( 'name' => 'Kokubu', 'lat' => 31.7436681, 'lon' => 130.7634464),
		'4613' => array( 'name' => 'Taniyama', 'lat' => 31.5267756, 'lon' => 130.5182922),
		'4614' => array( 'name' => 'Nishinoomote', 'lat' => 30.7325356, 'lon' => 130.9970786),
		'4615' => array( 'name' => 'Tarumizu', 'lat' => 31.4926939, 'lon' => 130.7012264),
		'4616' => array( 'name' => 'Satsumasendai', 'lat' => 31.813421, 'lon' => 130.3039789),
		'4617' => array( 'name' => 'Hioki', 'lat' => 31.6336972, 'lon' => 130.4024361),
		'4618' => array( 'name' => 'Soo', 'lat' => 31.6535068, 'lon' => 131.0194108),
		'4619' => array( 'name' => 'Kirishima', 'lat' => 31.7410148, 'lon' => 130.7632406),
		'4620' => array( 'name' => 'Ichikikushikino', 'lat' => 31.7146024, 'lon' => 130.2721599),
		'4621' => array( 'name' => 'Minamisatsuma', 'lat' => 31.4165805, 'lon' => 130.3236567),
		'4622' => array( 'name' => 'Shibushi', 'lat' => 31.4953083, 'lon' => 131.0456478),
		'4623' => array( 'name' => 'Amami', 'lat' => 28.3776614, 'lon' => 129.4938985),
		'4624' => array( 'name' => 'Minamikyushu', 'lat' => 31.3782842, 'lon' => 130.4416754),
		'4625' => array( 'name' => 'Isa', 'lat' => 32.0569877, 'lon' => 130.6130906),
		'4626' => array( 'name' => 'Aira', 'lat' => 31.78172, 'lon' => 130.59637),
		'4701' => array( 'name' => 'Naha', 'lat' => 26.2122345, 'lon' => 127.6791452),
		'4702' => array( 'name' => 'Ishikawa', 'lat' => 36.9890574, 'lon' => 136.8162839),
		'4703' => array( 'name' => 'Hirara', 'lat' => 24.8032045, 'lon' => 125.3029776),
		'4704' => array( 'name' => 'Ishigaki', 'lat' => 24.3439358, 'lon' => 124.1861835),
		'4705' => array( 'name' => 'Koza', 'lat' => 33.5193114, 'lon' => 135.820911),
		'4706' => array( 'name' => 'Ginowan', 'lat' => 26.2814968, 'lon' => 127.7784916),
		'4707' => array( 'name' => 'Gushikawa', 'lat' => 26.3589102, 'lon' => 127.8675878),
		'4708' => array( 'name' => 'Nago', 'lat' => 26.5919599, 'lon' => 127.9774759),
		'4709' => array( 'name' => 'Urasoe', 'lat' => 26.249754, 'lon' => 127.716591),
		'4710' => array( 'name' => 'Itoman', 'lat' => 26.106017, 'lon' => 127.686066),
		'4711' => array( 'name' => 'Okinawa', 'lat' => 26.5707754, 'lon' => 128.0255901),
		'4712' => array( 'name' => 'Tomigusuku', 'lat' => 26.1772381, 'lon' => 127.6863791),
		'4713' => array( 'name' => 'Uruma', 'lat' => 26.384705, 'lon' => 127.851324),
		'4714' => array( 'name' => 'Miyakojima', 'lat' => 24.8054647, 'lon' => 125.2811296),
		'4715' => array( 'name' => 'Nanjo', 'lat' => 26.1625434, 'lon' => 127.771152)
	);

	function get_jcc_array($bands, $postdata) {

		$jccArray = array_keys($this->jaCities);

		$cities = array(); // Used for keeping track of which cities that are not worked
		foreach ($jccArray as $city) {                         // Generating array for use in the table
			$cities[$city]['count'] = 0;                   // Inits each city's count
		}

		$qsl = $this->genfunctions->gen_qsl_from_postdata($postdata);


		foreach ($bands as $band) {
			foreach ($jccArray as $city) {                   // Generating array for use in the table
				$bandJcc[$city]['Number'] = $city;
				$bandJcc[$city]['City'] = $this->jaCities[$city]['name'];
				$bandJcc[$city][$band] = '-';                  // Sets all to dash to indicate no result
			}

			if ($postdata['worked'] != NULL) {
				$jccBand = $this->getJccWorked($this->location_list, $band, $postdata);
				foreach ($jccBand as $line) {
					$bandJcc[$line->col_cnty][$band] = '<div class="bg-danger awardsBgDanger"><a href=\'javascript:displayContacts("' . $line->col_cnty . '","' . $band . '","All","All","'. $postdata['mode'] . '","JCC", "")\'>W</a></div>';
					$cities[$line->col_cnty]['count']++;
				}
			}
			if ($postdata['confirmed'] != NULL) {
				$jccBand = $this->getJccConfirmed($this->location_list, $band, $postdata);
				foreach ($jccBand as $line) {
					$bandJcc[$line->col_cnty][$band] = '<div class="bg-success awardsBgSuccess"><a href=\'javascript:displayContacts("' . $line->col_cnty . '","' . $band . '","All","All","'. $postdata['mode'] . '","JCC", "'.$qsl.'")\'>C</a></div>';
					$cities[$line->col_cnty]['count']++;
				}
			}
		}

		// We want to remove the worked cities in the list, since we do not want to display them
		if ($postdata['worked'] == NULL) {
			$jccBand = $this->getJccWorked($this->location_list, $postdata['band'], $postdata);
			foreach ($jccBand as $line) {
				unset($bandJcc[$line->col_cnty]);
			}
		}

		// We want to remove the confirmed cities in the list, since we do not want to display them
		if ($postdata['confirmed'] == NULL) {
			$wasBand = $this->getJccConfirmed($this->location_list, $postdata['band'], $postdata);
			foreach ($wasBand as $line) {
				unset($bandJcc[$line->col_cnty]);
			}
		}

		if ($postdata['notworked'] == NULL) {
			if (isset($bandJcc)) {
				foreach ($jccArray as $city) {
					if ($cities[$city]['count'] == 0) {
						unset($bandJcc[$city]);
					};
				}
			}
		}

		if (isset($bandJcc)) {
			return $bandJcc;
		} else {
			return 0;
		}
	}

	function getJccBandConfirmed($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "select adif as waja, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";
		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getJccBandWorked($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "select adif as waja, name from dxcc_entities
			join (
				select col_dxcc from ".$this->config->item('table_name')." thcv
				where station_id in (" . $location_list .
				") and col_dxcc > 0";

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= " group by col_dxcc
				) x on dxcc_entities.adif = x.col_dxcc";;

		if ($postdata['includedeleted'] == NULL) {
			$sql .= " and dxcc_entities.end is null";
		}

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function returns all worked, but not confirmed cities
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getJccWorked($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "SELECT distinct col_cnty FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addStateToQuery();
		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		$sql .= " and not exists (select 1 from ". $this->config->item('table_name') .
			" where station_id in (". $location_list . ")" .
			" and col_cnty = thcv.col_cnty";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);
		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= $this->addStateToQuery();
		$sql .= ")";

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	/*
	 * Function returns all confirmed cities on given band and on LoTW or QSL
	 * $postdata contains data from the form, in this case Lotw or QSL are used
	 */
	function getJccConfirmed($location_list, $band, $postdata) {
		$bindings=[];
		$sql = "SELECT distinct col_cnty FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addStateToQuery();

		$sql .= $this->genfunctions->addBandToQuery($band,$bindings);

		$sql .= $this->genfunctions->addQslToQuery($postdata);

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}


	/*
	 * Function gets worked and confirmed summary on each band on the active stationprofile
	 */
	function get_jcc_summary($bands, $postdata) {
		foreach ($bands as $band) {
			if ($band != 'SAT') {
				$worked = $this->getSummaryByBand($band, $postdata, $this->location_list);
				$confirmed = $this->getSummaryByBandConfirmed($band, $postdata, $this->location_list);
				$jccSummary['worked'][$band] = $worked[0]->count;
				$jccSummary['confirmed'][$band] = $confirmed[0]->count;
			}
		}

		$workedTotal = $this->getSummaryByBand($postdata['band'], $postdata, $this->location_list);
		$confirmedTotal = $this->getSummaryByBandConfirmed($postdata['band'], $postdata, $this->location_list);

		$jccSummary['worked']['Total'] = $workedTotal[0]->count;
		$jccSummary['confirmed']['Total'] = $confirmedTotal[0]->count;

		if (in_array('SAT', $bands)) {
			$worked = $this->getSummaryByBand('SAT', $postdata, $this->location_list);
			$confirmed = $this->getSummaryByBandConfirmed('SAT', $postdata, $this->location_list);
			$jccSummary['worked']['SAT'] = $worked[0]->count;
			$jccSummary['confirmed']['SAT'] = $confirmed[0]->count;
		}

		return $jccSummary;
	}

	function getSummaryByBand($band, $postdata, $location_list) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_cnty) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[]=$band;
		} else if ($band == 'All') {
			$this->load->model('bands');
			$bandslots = $this->bands->get_worked_bands('was');
			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[]=$band;
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addStateToQuery();

		$query = $this->db->query($sql,$bindings);

		return $query->result();
	}

	function getSummaryByBandConfirmed($band, $postdata, $location_list) {
		$bindings=[];
		$sql = "SELECT count(distinct thcv.col_cnty) as count FROM " . $this->config->item('table_name') . " thcv";
		$sql .= " where station_id in (" . $location_list . ")";

		if ($band == 'SAT') {
			$sql .= " and thcv.col_prop_mode = ?";
			$bindings[]=$band;
		} else if ($band == 'All') {
			$this->load->model('bands');
			$bandslots = $this->bands->get_worked_bands('was');
			$bandslots_list = "'".implode("','",$bandslots)."'";

			$sql .= " and thcv.col_band in (" . $bandslots_list . ")" .
				" and thcv.col_prop_mode !='SAT'";
		} else {
			$sql .= " and thcv.col_prop_mode !='SAT'";
			$sql .= " and thcv.col_band = ?";
			$bindings[]=$band;
		}

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= $this->addStateToQuery();
		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}


	function addStateToQuery() {
		$sql = '';
		$sql .= " and COL_DXCC in ('339')";
		$sql .= " and (COL_CNTY LIKE '____' OR COL_CNTY LIKE '10____')";
		$sql .= " and COL_CNTY in (".implode(',', array_keys($this->jaCities)).")";
		return $sql;
	}

	function exportJcc($postdata) {
		$bindings=[];
		$sql = "SELECT distinct col_cnty FROM " . $this->config->item('table_name') . " thcv
			where station_id in (" . $this->location_list . ")";

		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}

		$sql .= $this->addStateToQuery();
		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= ' ORDER BY COL_CNTY ASC';

		$query = $this->db->query($sql,$bindings);

		$jccs = array();
		foreach($query->result() as $line) {
			$jccs[] = $line->col_cnty;
		}
		$qsos = array();
		foreach($jccs as $jcc) {
			$qso = $this->getFirstQso($this->location_list, $jcc, $postdata);
			$qsos[] = array('call' => $qso[0]->COL_CALL, 'date' => $qso[0]->COL_TIME_ON, 'band' => $qso[0]->COL_BAND, 'mode' => $qso[0]->COL_MODE, 'prop_mode' => $qso[0]->COL_PROP_MODE, 'cnty' => $qso[0]->COL_CNTY, 'jcc' => $this->jaCities[$qso[0]->COL_CNTY]['name']);
		}

		return $qsos;
	}

	function getFirstQso($location_list, $jcc, $postdata) {
		$bindings=[];
		$sql = 'SELECT COL_CNTY, COL_CALL, COL_TIME_ON, COL_BAND, COL_MODE, COL_PROP_MODE FROM '.$this->config->item('table_name').' t1
			WHERE station_id in ('.$location_list.')';
		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}
		$sql .= $this->addStateToQuery();
		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= ' AND COL_CNTY = ?';
		$bindings[]=$jcc;
		$sql .= ' ORDER BY COL_TIME_ON ASC LIMIT 1';
		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function fetch_jcc_wkd($postdata) {
		$bindings=[];
		$sql = 'SELECT DISTINCT `COL_CNTY` FROM '.$this->config->item('table_name').' WHERE 1
			and station_id in ('.$this->location_list.')';
		$sql .= $this->addStateToQuery();
		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}
		$sql .= ' ORDER BY COL_CNTY ASC';
		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function fetch_jcc_cnfm($postdata) {
		$bindings=[];
		$sql = 'SELECT DISTINCT `COL_CNTY` FROM '.$this->config->item('table_name').' WHERE 1
			and station_id in ('.$this->location_list.')';
		$sql .= $this->addStateToQuery();
		$sql .= $this->genfunctions->addBandToQuery($postdata['band'],$bindings);
		if ($postdata['mode'] != 'All') {
			$sql .= " and (col_mode = ? or col_submode = ?)";
			$bindings[]=$postdata['mode'];
			$bindings[]=$postdata['mode'];
		}
		$sql .= $this->genfunctions->addQslToQuery($postdata);
		$sql .= ' ORDER BY COL_CNTY ASC';
		$query = $this->db->query($sql,$bindings);
		return $query->result();
	}

	function jccCities() {
		return $this->jaCities;
	}

}
?>

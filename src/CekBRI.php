<?php  

/**
 * Cek Mutasi BRI termasuk dengan captcha solver.
 * @author Galih Azizi Firmansyah <galih@rempoah.com>
 * Telegram: @galihazizif
 */

require_once('simple_html_dom.php');

class CekBRI{

	const ua = "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/49.0.2623.75 Safari/537.36";
	const urlPrepareLogin = 'https://ib.bri.co.id/ib-bri/Login.html';
	const urlCaptcha = 'https://ib.bri.co.id/ib-bri/login/captcha';
	const urlPrepareMutasi = 'https://ib.bri.co.id/ib-bri/AccountStatement.html';
	const urlLogout = 'https://ib.bri.co.id/ib-bri/Logout.html';

	private $ch;
	private $config;
	private $result;
    private $cookie;
    private $dom;

	public function __construct($config){
        $cookie = 'cache/bri-cookie.txt';
		$this->cookie = $cookie;
		$this->dom = new simple_html_dom();
		$this->config = $config;
		$ch = curl_init();
    	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
    	curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
    	curl_setopt($ch, CURLOPT_USERAGENT, self::ua);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	$this->ch = $ch;

        try{
            $this->prepareLogin();
            $this->getCaptcha();
            $this->login();
            $this->prepareMutasi();
            $this->getMutasi();
            $this->logout();
        }catch(\Exception $e){
            return "Terjadi kegagalan, silahkan coba beberapa saat lagi.";
        }

	}

	private function prepareLogin(){
		$ch = $this->ch;
		curl_setopt($ch, CURLOPT_URL,self::urlPrepareLogin);
		$this->result = curl_exec($ch);
	}

	private function getCaptcha(){
		$ch = $this->ch;
		curl_setopt($ch, CURLOPT_URL,self::urlCaptcha);
		$captchaImg = curl_exec($ch);
		file_put_contents("cache/bri.captcha.png", $captchaImg);
	}

	private function login(){
		$dom = $this->dom;
		$dom->load($this->result);
		$form = $dom->find('form',0);
        if(empty($form))
            return false;
    	$csrfToken = $form->first_child()->first_child()->value;
        $tokenCode = $this->solveCaptcha();
        // $tokenCode = 1234;
    	// $tokenCode = readline("Enter BRI Captcha: ");
    	$config = $this->config;

    	$arrPostData = [
        	'csrf_token_newib' => $csrfToken,
        	'j_password' => $config['api']['password'],
        	'j_username' => $config['api']['username'],
        	'j_plain_username' => $config['api']['username'],
        	'preventAutoPass' => '',
        	'j_plain_password' => '',
        	'j_code' => $tokenCode,
        	'j_language' => 'in_ID'     
    	];

    	$postdata = http_build_query($arrPostData);
    	$ch = $this->ch;
    	curl_setopt($ch, CURLOPT_URL, $form->action);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	$result = curl_exec($ch);
    	usleep(100);
    	file_put_contents('cache/afterlogin.html', $result);

	}

	private function prepareMutasi(){
		$ch = $this->ch;
		curl_setopt($ch, CURLOPT_URL,self::urlPrepareMutasi);
		$this->result = curl_exec($ch);
	}

	private function getMutasi(){
		$dom = $this->dom;
		$config = $this->config;
		$ch = $this->ch;
		$dom->load($this->result);
		$form = $dom->find('form',0);
        if(empty($form))
            return false;
    	$token = $form->first_child()->first_child()->value;

    	$arrPostData = [
        	'csrf_token_newib' => $token,
        	'FROM_DATE' => date('Y-m-d'),
        	'TO_DATE' => date('Y-m-d'),
        	'download' => '',
        	'data-lenght' => '2',
        	'ACCOUNT_NO' => $config['nomor_rekening'],
        	'DDAY1' => $config['range']['tgl_akhir_obj']->format('d'),
        	'DMON1' => $config['range']['tgl_akhir_obj']->format('m'),
        	'DYEAR1' => $config['range']['tgl_akhir_obj']->format('Y'),
        	'DDAY2' => $config['range']['tgl_akhir_obj']->format('d'),     
        	'DMON2' => $config['range']['tgl_akhir_obj']->format('m'),
        	'DYEAR2' => $config['range']['tgl_akhir_obj']->format('Y'),
        	'VIEW_TYPE' => 1,
        	'MONTH' => $config['range']['tgl_akhir_obj']->format('m'),
        	'YEAR' => $config['range']['tgl_akhir_obj']->format('Y'),
        	'submitButton' => 'Tampilkan'
    	];

    	$postdata = http_build_query($arrPostData);

    	curl_setopt($ch, CURLOPT_URL, $form->action);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
    	curl_setopt($ch, CURLOPT_POST, 1);
    	$result = curl_exec($ch);
        $this->result = $result;
    	file_put_contents("cache/bri.hasil_mutasi.html",$result);

	}

	private function logout(){
		$ch = $this->ch;
		curl_setopt($ch, CURLOPT_URL, self::urlLogout);
		curl_exec($ch);
		// echo "Logout".PHP_EOL;
	}

    public function toArray(){
        $dom = $this->dom;
        $result = $this->result;
        // $result = file_get_contents("cache/bri.hasil_mutasi.html");
        $dom->load($result);


        try{
            $table = $dom->getElementById('tabel-saldo');
            if(empty($table)){
                return false;
            }

            $tbody = $table->children(1);

            $data = [];
            
            foreach($tbody->children() as $tr){
                $tgl = !empty($tr->children(0))?$tr->children(0)->innertext():"";
                $judul = !empty($tr->children(1))?strip_tags($tr->children(1)->innertext()):"";
                $nominal1 = !empty($tr->children(2))?$tr->children(2)->innertext():"";
                $nominal2 = !empty($tr->children(3))?$tr->children(3)->innertext():"";
                $nominal3 = !empty($tr->children(4))?$tr->children(4)->innertext():"";
               
                $data[] = [
                    $tgl,
                    $judul,
                    $this->fixAngka($nominal1),
                    $this->fixAngka($nominal2),
                    $this->fixAngka($nominal3)
                ];
            }

            $toBeDeleted[] = 0;
            
            $total = count($data);
            $endOffsetDelete = $total - 4;
            for($i = $total; $i > $endOffsetDelete; $i--){
                $toBeDeleted[] = $i;
            }

            foreach($toBeDeleted as $tbd){
                unset($data[$tbd]);
            }

            return $data;
        }catch(\Exception $e){
            return $e->getMessage();
        }
        

    }

    public function solveCaptcha(){
        $captchaBri = "cache/bri.captcha.png";
        exec("convert cache/bri.captcha.png -flatten -fuzz 20% -trim +repage -white-threshold 5000 -type bilevel cache/bri.captcha.png");
        exec('tesseract "cache/bri.captcha.png" "cache/bri.hasil" -psm 7 -c "tessedit_char_whitelist=01234567890"');
        return file_get_contents("cache/bri.hasil.txt");
    }

    private function fixAngka($string){
        if(!is_null($string)){
            $string = substr($string, 0, -3);
            $string = str_replace('.', '', $string);
            return (int)$string;
        }
        return 0;
    }


}


?>
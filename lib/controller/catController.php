<?php

include_once INIT::$MODEL_ROOT . "/queries.php";
include INIT::$UTILS_ROOT . "/mymemory_queries_temp.php";
include INIT::$UTILS_ROOT . "/filetype.class.php";
include INIT::$UTILS_ROOT . "/langs/languages.inc.php";

/**
 * Description of catController
 *
 * @author antonio
 */
class catController extends viewcontroller {

    //put your code here    
    private $data = array();
    private $cid = "";
    private $jid = "";
    private $password="";
    private $source="";
    private $pname = "";
    private $create_date = "";
    private $filetype_handler = null;
    private $start_from = 0;

    public function __construct() {
       // echo ".........\n";
        parent::__construct();
        parent::makeTemplate("index.html");
        $this->jid = $this->get_from_get_post("jid");
	$this->password=$this->get_from_get_post("password");
        $this->start_from = $this->get_from_get_post("start");
        if (is_null($this->start_from)) {
            $this->start_from = 0;
        }

	if (is_null($this->jid) and is_null($this->password)) {
            header("Location: /translate/pname/src-trg/23-P1Z4c");
	    exit(0);
        }
    }

    private function stripTagesFromSource($text) {
        //       echo "<pre>";
        $pattern_g_o = '|(<.*?>)|';
        $pattern_g_c = '|(</.*?>)|';
        $pattern_x = '|(<.*?/>)|';

        // echo "first  -->  $text \n";
        $text = preg_replace($pattern_x, "", $text);
        // echo "after1  --> $text\n";

        $text = preg_replace($pattern_g_o, "", $text);
        //  echo "after2  -->  $text \n";
//
        $text = preg_replace($pattern_g_c, "", $text);
        return $text;
    }

    public function doAction() {
        $lang_handler=languages::getInstance("en");       

        $data = getSegments($this->jid, $this->password, $this->start_from);
//        echo "<pre>";
//        print_r ($data);
//        exit;
//        
        $first_not_translated_found = false;
        foreach ($data as $i => $seg) {
            $seg['segment'] = $this->stripTagesFromSource($seg['segment']);
            $seg['segment'] = trim($seg['segment']);

            if (empty($seg['segment'])) {
                continue;
            }

            if (empty($this->pname)) {
                $this->pname = $seg['pname'];
            }

            if (empty($this->last_opened_segment)) {
                $this->last_opened_segment = $seg['last_opened_segment'];
            }
			
            if (empty($this->cid)) {
                $this->cid = $seg['cid'];
            }

            if (empty($this->pid)) {
                $this->pid = $seg['pid'];
            }

            if (empty($this->tid)) {
                $this->cid = $seg['tid'];
            }

            if (empty($this->create_date)) {
                $this->create_date = $seg['create_date'];
            }

		    if (empty($this->source)) {
				$s=explode("-", $seg['source']);
				$source=strtoupper($s[0]);
	            $this->source = $source;
	        }
            
            $id_file = $seg['id_file'];
            if (!isset($this->data["$id_file"])) {                
                $this->data["$id_file"]['jid'] = $seg['jid'];		
                $this->data["$id_file"]["filename"] = $seg['filename'];
                $this->data["$id_file"]["mime_type"] = $seg['mime_type'];
                $this->data["$id_file"]['id_segment_start'] = $seg['id_segment_start'];
                $this->data["$id_file"]['id_segment_end'] = $seg['id_segment_end'];                
                $this->data["$id_file"]['source']=$lang_handler->iso2Language($seg['source']);
                $this->data["$id_file"]['target']=$lang_handler->iso2Language($seg['target']);
                $this->data["$id_file"]['source_code']=$seg['source'];
                $this->data["$id_file"]['target_code']=$seg['target'];
				$this->data["$id_file"]['segments'] = array();
            }
            //if (count($this->data["$id_file"]['segments'])>100){continue;}
            $this->filetype_handler = new filetype($seg['mime_type']);



            unset($seg['id_file']);
	    	unset($seg['source']);
            unset($seg['target']);
	    	unset($seg['source_code']);
            unset($seg['target_code']);
            unset($seg['mime_type']);
            unset($seg['filename']);
            unset($seg['jid']);
            unset($seg['pid']);
            unset($seg['cid']);
            unset($seg['tid']);
            unset($seg['pname']);
            unset($seg['create_date']);
            unset($seg['id_segment_end']);
            unset($seg['id_segment_start']);

            $seg['segment'] = $this->filetype_handler->parse($seg['segment']);

         /*   if (!$first_not_translated_found and empty($seg['translation'])) { //get matches only for the first segment                
                $first_not_translated_found = true;
                $matches = array();
                $matches = getFromMM($seg['segment']);

                $matches = array_slice($matches, 0, INIT::$DEFAULT_NUM_RESULTS_FROM_TM);

                $seg['matches'] = $matches;

                //$seg['matches_no_mt']=0;
                //foreach ($matches as $m){
                //    if ($m['created-by']!='MT'){
                //        $seg['matches_no_mt']+=1;
                //    }
                //}
                $seg['css_loaded'] = "loaded";
            }
          * 
          * 
          */

            /*if (!empty($seg['translation'])) {
                $seg['css_loaded'] = "loaded";
            }*/

            $this->data["$id_file"]['segments'][] = $seg;
        }
    //   echo "<pre>";
    //   print_r($this->data);
    //   exit;
    }

    public function setTemplateVars() {
        $this->template->data = $this->data;
        $this->template->cid = $this->cid;
        $this->template->create_date = $this->create_date;
        $this->template->pname = $this->pname;
		$this->template->pid=$this->pid;
		$this->template->source=$this->source;
		//$this->template->source_code=$this->source_code;
		//$this->template->target_code=$this->target_code;
		$this->template->last_opened_segment=$this->last_opened_segment;


        //echo "<pre>";
        //print_r ($this->template);
        //exit;
        ;
    }

}
?>


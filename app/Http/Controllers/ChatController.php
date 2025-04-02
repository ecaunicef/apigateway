<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use App\Traits\ApiResponser;
use App\Traits\ConsumeExternalService;
use DB;

class ChatController extends BaseController 
{
    use ConsumeExternalService;
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $request;
    private $baseUri;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
    */

    public function __construct()
    {
       $this->baseUri = config('servicesurl.chat.base_uri');
    }

    public function get(Request $request,$slug) {
        
	    // echo $this->baseUri;die;
        //check if url has query string and append to url 
        $queryString = $request->getQueryString();
        $slug = ($request->getQueryString())?$slug.'?'.$queryString: $slug;
        // echo "qqqq".$slug;
       return  $this->performRequest('GET', $this->baseUri.'/'.$slug, [],$request->headers->all(),$request);
    }
    
    public function post(Request $request,$slug) {
        return $this->performRequest('POST', '/books', $data,$request);
    }

    public function otherPostAction(Request $request,$slug) {

        $header = $this->generateHeader($request->headers->all());
        return $this->performRequest('POST', $this->baseUri.'/'.$slug, $request->all(),$header, $request);
    }

    protected function generateHeader($header){

        $get_first = function($x){
            return $x[0];
        };

        return array_map($get_first, $header);
        
    }
}
<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Pion\Laravel\ChunkUpload\Exceptions\UploadMissingFileException;
use Pion\Laravel\ChunkUpload\Handler\AbstractHandler;
use Pion\Laravel\ChunkUpload\Handler\HandlerFactory;
use Pion\Laravel\ChunkUpload\Receiver\FileReceiver;

use App\User;
use Illuminate\Routing\Controller as BaseController;
use Jenssegers\Mongodb\Eloquent\Model as Eloquent;
use App\Traits\ApiResponser;
use App\Traits\ConsumeExternalService;
use DB;


class DataImportController extends BaseController 
{
    use ConsumeExternalService;
    /**
     * The request instance.
     *
     * @var \Illuminate\Http\Request
     */
    private $baseUri;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
    */

    public function __construct()
    {
       $this->baseUri = config('servicesurl.dataprocessing.base_uri');
    }

    public function get(Request $request,$slug) {
        return $this->performRequest('GET', $this->baseUri.'/'.$slug, [],[], $request);
    }
    
    

    public function post(Request $request) {
        ini_set('memory_limit', -1);
        $receiver = new FileReceiver("files", $request, HandlerFactory::classFromRequest($request));
        if ($receiver->isUploaded() === false) {
            throw new UploadMissingFileException();
        }
        // receive the file
        $save = $receiver->receive();

        if ($save->isFinished()) {
            return $this->saveFile($save->getFile(), $request->get('import_for'), $request->get('translation_lang'),$request->get('user_id'), $request-> get('data_set_id'), $request->get("plan_id"), $request);
        }

        // we are in chunk mode, lets send the current progress
        /** @var AbstractHandler $handler */
        $handler = $save->handler();
        return response()->json([
            "done" => $handler->getPercentageDone(),
            'status' => true
        ]);
    }


    protected function saveFile(UploadedFile $file, $import_type, $translationLang,$user_id, $data_set_id, $plan_id,$request)
    {
       
        // $import_type = e($import_type);
        // print_r($import_type);
        // die();
        $fileName = $this->createFilename($file);

        // Group files by mime type
        $mime = str_replace('/', '-', $file->getMimeType());
        // echo $mime;

        $valid_mime = [
            'text-plain',
            'application-vnd.ms-office',
            'application-vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application-vnd.ms-excel',
            'application-octet-stream',
            'text-x-Algol68',
            'text-csv'
        ];


        if(in_array($mime,$valid_mime) || (in_array($import_type,[36,37,38]) && $file->getClientOriginalExtension() == 'xlsx') ){
            $dateFolder = date("Y-m-W");
            $finalPath = base_path("containerdata");

            // move the file name
            $file->move($finalPath, $fileName);   
            $currentDate =  date('Y-m-d h:i:s');
    

            if($import_type == 1){
                $tempData = DB::table('uploaded_file_info')->insertGetId([
                    'user_id' => $user_id,
                    'file_name' => $fileName,
                    'type' => $import_type,
                    'language_type'=>$request->get('language_type'),
                    'createdAt' => $currentDate,
                    'updatedAt' => date('Y-m-d h:i:s')
                ]);
            }
             else{
                $tempData = DB::table('uploaded_file_info')->insertGetId([
                    'user_id' => $user_id,
                    'file_name' => $fileName,
                    'type' => $import_type,
                    'createdAt' => $currentDate,
                    'updatedAt' => date('Y-m-d h:i:s')
                ]);
            }



            $tempDataArray = ['id' => $tempData];
            $id = $tempDataArray['id'];
            $url = '';
            $fileValidationUrl = '';
            //assign url for import type

            switch ($import_type) {
                //data
                case 1:
                   
                    $fileValidationUrl = $this->baseUri.'/language/import/data/filevalidation/'.$import_type.'/'.$id.'/'.$user_id;
                    $url = $this->baseUri.'/language/import/data/'.$import_type.'/'.$id.'/'.$user_id;
                    break;
                //area    
                case 4:
                    $fileValidationUrl = $this->baseUri.'/area/import/data/filevalidation/'.$import_type.'/'.$id.'/'.$user_id;;
                    $url = $this->baseUri.'/area/import/data/'.$import_type.'/'.$id.'/'.$user_id;
                    break;    
                 //data exchange processed data import upload
                case 21:
                    return response()->json([
                        'path' => $finalPath,
                        'name' => $fileName,
                        'mime_type' => $mime,
                        'import_id' => $id,
                        'currentDate' => $currentDate,
                    ]);

                break;     
                default:
            }
            if($fileValidationUrl) {          
                $_FILES = [];
                
                $temp = $this->performRequest('GET', $fileValidationUrl, [],[],$request);
                $validationStatus = $temp->getBody()->getContents();
               
                $res = json_decode($validationStatus);
                if($res->status == 200) {

                    if($url) {
                        
                        exec("curl $url  > /dev/null 2>&1 &");
                        return response()->json([
                            'path' => $finalPath,
                            'name' => $fileName,
                            'mime_type' => $mime,
                            'import_id' => $id,
                            'currentDate' => $currentDate,
                        ]);
                    }
                } else {
                    return response()->json([
                        'mime_type' => '',
                        'message' => $res->msg,
                        'import_id'=> $id
                    ],400);
                }

            }else if(in_array($import_type,[26,27,28,29,30,31,32,33,34])){
                if($url) {
                        
                    exec("curl $url  > /dev/null 2>&1 &");
                    return response()->json([
                        'path' => $finalPath,
                        'name' => $fileName,
                        'mime_type' => $mime,
                        'import_id' => $id,
                        'currentDate' => $currentDate,
                    ]);
                }
            }
           
             return response()->json([
                'mime_type' => $mime,
                'message' => 'Invalid import type',
            ],400);

        }else{
            return response()->json([
                'mime_type' => $mime,
                'message' => 'Invalid file',
            ],400);
        }
        
    }


    protected function createFilename(UploadedFile $file) {
        $filename="";
        $extension = $file->getClientOriginalExtension();
        $filename.= str_replace(".".$extension, "", $file->getClientOriginalName()); // Filename without extension
        // Add timestamp hash to name of the file
        //$filename .= "_" . md5(time()) . "." . $extension;
        $filename .= "_" . date('Y_m_d_h_i_s') . "." . $extension;
        return $filename;
    }


    public function otherPostAction(Request $request,$slug) {
        $header = $this->generateHeader($request->headers->all());
        return $this->performRequest('POST', $this->baseUri.'/'.$slug, $request->all(),$header, $request);
    }

    public function put(Request $request,$slug) {
        // redirect to data import update api 
        $header = $this->generateHeader($request->headers->all());
        return $this->performRequest('PUT', $this->baseUri.'/'.$slug, $request->all(),$header,$request);
    }

    protected function generateHeader($header){

        $get_first = function($x){
            return $x[0];
        };

        return array_map($get_first, $header);
        
    }

}

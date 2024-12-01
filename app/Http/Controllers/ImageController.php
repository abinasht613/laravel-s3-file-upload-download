<?php

namespace App\Http\Controllers;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Aws\S3\S3Client; 
use Aws\S3\Exception\S3Exception;



class ImageController extends Controller
{
    public function upload()
    {
        return view('upload');
    }
    public function store1(Request $request)
    {
        try{
            $request->validate([
                'title' => 'required',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);
            if ($request->hasFile('image')) {
                $extension  = request()->file('image')->getClientOriginalExtension(); //This is to get the extension of the image file just uploaded
                $image_name = time() .'_' . $request->title . '.' . $extension;
                $path = $request->file('image')->storeAs(
                    'images',
                    $image_name,
                    's3'
                );
                #$ss="Screenshot from 2024-11-03 20-28-19.png";
                #$existss = Storage::disk('s3')->exists($ss);
                dd($path);
                
                Image::create([
                    'title'=>$request->title,
                    'image'=>$path
                ]);
                return redirect()->back()->with([
                    'message'=> "Image uploaded successfully $path gggg",
                ]);
            }
        }
        catch(Exception $e){
            dd($e->getMessage());
        }
    }

    public function store(Request $request){

        $request->validate([
            'image' => 'required|mimes:pdf,docx,doc,pptx,ppt,xls,xlsx,jpg,png|max:2048'
        ]);
        $file = $request->file('image');

        //create s3 client
        $s3 = new S3Client([
            'version' => 'latest',
            'region'  => env('AWS_DEFAULT_REGION'),
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ]
        ]);
        $extension  = request()->file('image')->getClientOriginalExtension(); //This is to get the extension of the image file just uploaded
        $keyname = 'uploads/' .time() . '.' . $extension;
        //create bucket
        if (!$s3->doesBucketExist(env('AWS_BUCKET'))) {
            // Create bucket if it doesn't exist
            try{
                $s3->createBucket([
                    'Bucket' => env('AWS_BUCKET'),
                ]);
            } catch (S3Exception $e) {
                return response()->json([
                    'Bucket Creation Failed' => $e->getMessage()
                ]);
            }
        }
        //upload file
        try {
            $result = $s3->putObject([
                'Bucket' => env('AWS_BUCKET'),
                'Key'    => $keyname,
                'Body'   => fopen($file, 'r'),
                'ACL'    => 'public-read'
            ]);
            // dd($result);
            // Print the URL to the object.
            
            Image::create([
                'title'=>$request->title,
                'image'=>$result['ObjectURL']
            ]);
            return response()->json([
                'message' => 'File uploaded successfully',
                'file link' => $result['ObjectURL'],
            ]);
        } catch (S3Exception $e) {
            return response()->json([
                'Upload Failed' => $e->getMessage()
            ]);
        }
    }

    public function delete(){
        try{
            $path="https://infotute-files.s3.ap-south-1.amazonaws.com/uploads/1733aaa045247.png";
            $file_path = parse_url($path);
            // dd($file_path);
            // $s3 = new S3Client([
            //     'version' => 'latest',
            //     'region'  => env('AWS_DEFAULT_REGION'),
            //     'credentials' => [
            //         'key'    => env('AWS_ACCESS_KEY_ID'),
            //         'secret' => env('AWS_SECRET_ACCESS_KEY'),
            //     ]
            // ]);
            // $result = $s3->deleteObject(array(
            //     'Bucket' => env('AWS_BUCKET'),
            //     'Key'    => "uploads/1733045033.png"
            // ));
            
            
            $result=Storage::disk('s3')->delete($file_path['path']);
            
            dd($result);
        }
        catch(Exception $e){
            return response()->json([
                'Upload Failed' => $e->getMessage()
            ]);
        }
    }
}

<?php
 
 
 
    function saveWxImg($url)
    {
        
      
        
        $header = array(
            'User-Agent: Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:45.0) Gecko/20100101 Firefox/45.0',
            'Accept-Language: zh-CN,zh;q=0.8,en-US;q=0.5,en;q=0.3',
            'Accept-Encoding: gzip, deflate'
        );
       
        $curl   = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip');
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        $data = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($code == 200) { //把URL格式的图片转成base64_encode格式的！    
            $imgBase64Code = "data:image/jpeg;base64," . base64_encode($data);
        }
        
        $img_content = $imgBase64Code; //图片内容
        //echo $img_content;exit;
        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $img_content, $result)) {
            $type = $result[2]; //得到图片类型png?jpg?gif? 
            $rand = randstr(30);
            $new_file = "/var/www/html/assets/tmp/" . $rand . ".{$type}";
            if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $img_content)))) {
                 return $new_file;

            } else{
                 return false;
         }
        }
        
    }
    
    function resize_imagepng($file, $w, $h)
    {
        list($width, $height) = getimagesize($file);

        $src = imagecreatefrompng($file);
        $dst = imagecreatetruecolor($w, $h);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $w, $h, $width, $height);
        imagedestroy($src);
        return $dst;
    }
    


   function    circle($image_s,$newwidth,$newheight){

        $width = imagesx($image_s);
        $height = imagesy($image_s);
        $image = imagecreatetruecolor($newwidth, $newheight);
        imagealphablending($image, true);
        imagecopyresampled($image, $image_s, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
        //create masking
        $mask = imagecreatetruecolor($newwidth, $newheight);
        $transparent = imagecolorallocate($mask, 255, 0, 0);
        imagecolortransparent($mask,$transparent);
        imagefilledellipse($mask, $newwidth/2, $newheight/2, $newwidth, $newheight, $transparent);
        $red = imagecolorallocate($mask, 0, 0, 0);
        imagecopymerge($image, $mask, 0, 0, 0, 0, $newwidth, $newheight, 100);
        imagecolortransparent($image,$red);
        imagefill($image, 0, 0, $red);
        imagedestroy($mask);
        return $image;

     }


 
?>

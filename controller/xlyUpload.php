<?php
    class clsUpload{
        public function Upload($file){
            if(!$this->checkType($file["type"]))
                return -2;
            if(!$this->checkSize($file["size"]))
                return -1;
            $newname = $this->setDes($file["name"]);
            if(move_uploaded_file($file["tmp_name"],"../imagee/".$newname)){
                return $newname;
            }return 0;
        }

        public function checkSize($size){
            $cont = 3*1024*1024;
            if($size>$cont)
                return false;
            return true;
        }

        public function checkType($type){
            $arrType= array("image/png", "image/jpeg");
            if(in_array($type,$arrType))
                return true;
            return false;
        }

        public function setDes($name){
            //$folder= "image/";
            $name_arr= explode(".",$name);
            $ext = ".".$name_arr[count($name_arr)-1];
            //Doi ten file thanh mssv_thoigianuploads
            $new_name= "21110151_20005661_".time();
            $des= $new_name.$ext;
            return $des;
        }
    }
?>

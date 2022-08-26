<?php
//////////////////////////////////////////
//
//  Captacha 3D
//
//  Original code by KAndy
//  URL: http://kandy.habrahabr.ru/
//
//  Modified by Megas
//  URL: http://qmegas.info
//  Version 3 (04.11.2009)
//                                   
//////////////////////////////////////////

$capthca = new Capthca3d();
$capthca->render();

class Capthca3d
{
    const CHARS = '1234567890';
    protected $hypot = 5;
    protected $image = null;

    protected $text = '';

    public function __construct()
    {
        $this->generateCode();
    }

    protected function generateCode()
    {
        $chars = self::CHARS;
        for($i =0; $i<4; $i++)
        {
            $this->text .= $chars{mt_rand(0,strlen($chars)-1)};
        }
    }

    public function getText()
    {
        return $this->text;
    }

    protected function preloadProection()
    {
        $angels = array(45,50,55,60,65,70,75,80,85,95,100,105,110,115,120,125,130,135);
        $move_x = array(0,0,0,0,-4,-4,-4,-2,-1,2,5,7,9,11,13,17,20,24);
        $move_y = array(27,25,22,19,17,15,14,11,7,3,1,-1,-1,-3,-3,-5,-5,-6);
        $i = mt_rand(0,count($angels)-1);

        $a = deg2rad(35); //Angle of camera moved up
        $b = deg2rad($angels[$i]); //Angle of camera moved aside

        $this->p_xx = cos($b);
        $this->p_xy = 0;
        $this->p_xz = -sin($b);

        $this->p_yx = sin($a)*sin($b);
        $this->p_yy = cos($a);
        $this->p_yz = sin($a)*cos($b);

        $this->p_add_x = $move_x[$i] * $this->hypot;
        $this->p_add_y = $move_y[$i] * $this->hypot;
    }

    protected function getProection($x1,$y1,$z1)
    {
        $x = $x1 * $this->hypot;
        $y = $z1 * $this->hypot;
        $z = -$y1 * $this->hypot;

        $cx = $this->p_xx*$x + $this->p_xy*$y + $this->p_xz*$z + $this->p_add_x;
        $cy = $this->p_yx*$x + $this->p_yy*$y + $this->p_yz*$z + $this->p_add_y;

        return array('x'=>$cx, 'y'=>$cy);
    }

    protected function zFunction($x,$y)
    {
        $z = (imagecolorat($this->image,$y/2,$x/2)>0)?1.6:0;
        if( $z != 0 )
        {
            $z += mt_rand(0,60)/100;
        }
        $z += 1.4 * sin(($x+$this->startX)*pi()/15)*sin(($y+$this->startY)*pi()/15);
        return $z;
    }

    protected function getMyRGB($color_style, $color_max_val, $cur_val, &$r, &$g, &$b)
    {
        switch ($color_style)
        {
            case 1:
                $r = $color_max_val;
                $g = $b = $cur_val;
                break;
            case 2:
                $g = $color_max_val;
                $r = $b = $cur_val;
                break;
            case 3:
                $b = $color_max_val;
                $g = $r = $cur_val;
                break;
            /* case 4:
                $r = $g = $color_max_val;
                $b = $cur_val;
                break; */
            case 4:
                $r = $b = $color_max_val;
                $g = $cur_val;
                break;
            case 5:
                $b = $g = $color_max_val;
                $r = $cur_val;
                break;
            case 6:
                $r = $b = $g = $cur_val;
                break;
        }
    }

    public function render()
    {
        $xx = 35;
        $yy = 80;

        $this->preloadProection();

        $this->image = imageCreateTrueColor($yy * $this->hypot, $xx * $this->hypot);

        $whiteColor = imageColorAllocate($this->image,255,255,255);
        imageFilledRectangle($this->image,0,0,$yy * $this->hypot, $xx * $this->hypot,$whiteColor);

        $textColor = imageColorAllocate($this->image,0,0,0);
        imageString($this->image, 5, 3, 0, $this->text, $textColor);

        $this->startX = mt_rand(0,$xx);
        $this->startY = mt_rand(0,$yy);

        $coordinates = array();

        for($x = 0; $x < $xx + 1; $x++)
        {
            for($y = 0; $y < $yy + 1; $y++)
            {
                $z = $this->zFunction($x,$y);
                $coordinates[$x][$y] = $this->getProection($x,$y,$z);
                $coordinates[$x][$y]['z'] = $z;
            }
        }

        // ======= Color ========== //
        if (mt_rand(1,2)==1)
        {
            //Min max
            $max = 0; $min = 0;
            for($x = 0; $x < $xx; $x++)
            {
                for($y = 0; $y < $yy; $y++)
                {
                    $c = $coordinates[$x][$y]['z'];
                    if ($c>$max) $max = $c;
                    if ($c<$min) $min = $c;
                }
            }
            //Normalize
            $added = -$min;
            $per_one = ($max-$min)/256;
            for($x = 0; $x < $xx; $x++)
            {
                for($y = 0; $y < $yy; $y++)
                {
                    $coordinates[$x][$y]['col'] = intval(($coordinates[$x][$y]['z']+$added)/$per_one);
                    if ($coordinates[$x][$y]['col']>255) $coordinates[$x][$y]['col'] = 255;
                }
            }
        } else {
            $per_one = 256/$yy;
            for($x = 0; $x < $xx; $x++)
            {
                for($y = 0; $y < $yy; $y++)
                {
                    $coordinates[$x][$y]['col'] = intval($per_one*$y);
                    if ($coordinates[$x][$y]['col']>255) $coordinates[$x][$y]['col'] = 255;
                }
            }
        }
        $color_style = mt_rand(1,6);
        $color_max_val = mt_rand(0,255);
        //  ==== end color === //

        for($x = 0; $x < $xx; $x++)
        {
            for($y = 0; $y < $yy; $y++)
            {
                $coord = array();
                $coord[] = $coordinates[$x][$y]['x'];
                $coord[] = $coordinates[$x][$y]['y'];

                $coord[] = $coordinates[$x+1][$y]['x'];
                $coord[] = $coordinates[$x+1][$y]['y'];

                $coord[] = $coordinates[$x+1][$y+1]['x'];
                $coord[] = $coordinates[$x+1][$y+1]['y'];

                $coord[] = $coordinates[$x][$y+1]['x'];
                $coord[] = $coordinates[$x][$y+1]['y'];

                $this->getMyRGB($color_style, $color_max_val, $coordinates[$x][$y]['col'], $r, $g, $b);
                $linesColor = imageColorAllocate($this->image, $r, $g, $b);
                imageFilledPolygon($this->image, $coord, 4, $whiteColor);
                imagePolygon($this->image, $coord, 4, $linesColor);
             }
         }

        imageString($this->image, 5, 3, 0, $this->text, $whiteColor);
        header('Content-Type: image/png');
        imagepng($this->image);
        imagedestroy($this->image);
    }
}
?>

<?php
namespace App\Controllers;
use App\Core\View\View;
use App\Core\Database\DB;

class CartController{
function index(){
$cart=$_SESSION['cart']??[];
$products=DB::query("SELECT * FROM products")->fetchAll();
$map=[];foreach($products as $p){$map[$p['id']]=$p;}
$total=0;foreach($cart as $id=>$q){$total+=$map[$id]['price']*$q;}
return View::render('cart.index',['cart'=>$cart,'products'=>$map,'total'=>$total]);
}
function add($id){
        // 🔐 CSRF CHECK
        if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            die('CSRF token mismatch');
        }
	$_SESSION['cart'][$id]=($_SESSION['cart'][$id]??0)+1; header('Location:/cart'); exit;}
function addByGet($id){
    $_SESSION['cart'][$id]=($_SESSION['cart'][$id]??0)+1;
    header('Location:/cart');
    exit;
}
function remove($id){
        // 🔐 CSRF CHECK
        if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            die('CSRF token mismatch');
        }
unset($_SESSION['cart'][$id]); header('Location:/cart'); exit;}
}

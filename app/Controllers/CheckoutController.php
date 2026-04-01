<?php
namespace App\Controllers;
use App\Core\Database\DB;

class CheckoutController{
function index(){return "Checkout";}
function process(){
        // 🔐 CSRF CHECK
        if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
            die('CSRF token mismatch');
        }
$cart=$_SESSION['cart']??[];
$user=$_SESSION['user']??null;
if(!$user)return "Login required";

$products=DB::query("SELECT * FROM products")->fetchAll();
$map=[]; foreach($products as $p){$map[$p['id']]=$p;}
$total=0; foreach($cart as $id=>$q){$total+=$map[$id]['price']*$q;}

DB::query("INSERT INTO orders(user_id,total) VALUES(?,?)",[$user['id'],$total]);
$orderId=DB::query("SELECT LAST_INSERT_ID()")->fetchColumn();

foreach($cart as $id=>$q){
DB::query("INSERT INTO order_items(order_id,product_id,qty,price) VALUES(?,?,?,?)",[$orderId,$id,$q,$map[$id]['price']]);
}

$_SESSION['cart']=[];
return "Order placed";
}}

<?php
namespace Admin\Model;
class PageModel{
	
    private $roll_page = 6;//栏位
	private $cool_page =3;
	private $cool_page_ceil = 3;
	private $page_size = 20;
	
	public function getPage($now_page , $count ,$url = null , $pagesize = null){
		
		if(isset($pagesize) && $pagesize){
			$this->page_size = $pagesize;
		}
		
		if(isset($url) && $url){
			$url .= "&";
		}else{
			$url = "index.html?";
		}
		
		$total_pages = ceil($count/$this->page_size);//总页数
		$roll_page = $this->roll_page;//栏位
		$cool_page =$this->cool_page;
		$cool_page_ceil = $this->cool_page_ceil;
		$link_page = "";
		
		$link_page .=     ' <div class="pagin"><div class="message">共<i class="blue">'.$count.'</i>条记录，当前显示第&nbsp;<i class="blue"> '.$now_page.' &nbsp;</i>页</div>  <ul class="paginList"> ';

		if((int)$count > (int)$pagesize){
			if($now_page !=1){
			$href = "".$url."page=".(int)($now_page-1)."";
			$link_page .= '<li class="paginItem"><a href= '.$href.'> <span class="pagepre"></span></a></li>';
		}
		
		for($i = 1; $i <= $roll_page; $i++){
			if(($now_page - $cool_page) <= 0 ){
				$page = $i;
			}elseif(($now_page + $cool_page - 1) >= $total_pages){
				$page = $total_pages - $roll_page + $i;
			}else{
				$page = $now_page - $cool_page_ceil + $i;
			}
			if($page > 0 && $page != $now_page){
				if($page <= $total_pages){
					$href = "".$url."page=".$page."";
					$link_page .= '<li class="paginItem"><a href= '.$href.'> '.$page.'</a></li>';
				}else{
					break;
				}
			}else{
				if($page > 0 && $total_pages != 1){
					$href = "".$url."page=".$page."";
					$link_page .= '<li class="paginItem current"><a href= '.$href.'> '.$page.'</a></li>';
				}
			}
		}
		
		if($now_page !=$total_pages){
			$href = "".$url."page=".(int)($now_page+1)."";
			$link_page .= '<li class="paginItem"><a href= '.$href.'> <span class="pagenxt"></span></a></li>';
		}
		
		$link_page .='</ul> </div>';
		}
		
		
		return $link_page;
	}
	
}
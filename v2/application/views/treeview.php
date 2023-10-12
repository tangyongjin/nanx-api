<!DOCTYPE html>
<HTML>
 <HEAD>
  <TITLE>我的伙伴</TITLE>
  <meta http-equiv="content-type" content="text/html; charset=UTF-8">
  <meta http-equiv="pragma" content="no-cache" />

  <link rel="stylesheet" href="https://web.golf-brother.com/ds/static/res/css/metroStyle/metroStyle.css" type="text/css">
  <script type="text/javascript" src="https://web.golf-brother.com/ds/static/res/js/zTree/jquery-1.4.4.min.js"></script>
  <script type="text/javascript" src="https://web.golf-brother.com/ds/static/res/js/zTree/jquery.ztree.all.js"></script>
  
 </HEAD>
 
<BODY>

<div>
	 
   <ul id="treeDemo" class="ztree"></ul>
   <div id="debug_ready"></div>
</div>
</BODY>
</HTML>


<script type="text/javascript">

 $(document).ready(function(){
  
 
     
      var treejson=<?php echo  $treejson;?>;

      console.log(treejson);

 
     console.log(treejson)

     var zTreeObj;
     var setting = {}
     zTreeObj = $.fn.zTree.init($("#treeDemo"), setting, treejson);
     var node = zTreeObj.getNodes(); //可以获取所有的父节点
     var nodes = zTreeObj.transformToArray(node); //获取树所有节点
     console.log(nodes)

});


 

</script>

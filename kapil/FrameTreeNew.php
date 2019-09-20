<?php 
require_once '../db.php';

$mysqli = new mysqli($dbHost, $dbUser, $dbPwd, $dbName);
// Check connection
if (!$mysqli) {
    die("Connection failed: " . mysqli_connect_error());
}

$result = $mysqli->query("SELECT DISTINCT product_sku, mws_model FROM mws WHERE csv_group_product = 'group' AND mws_model<>''");
$arr = array();
foreach($result as $res){
  $arr_us[] = array(id => 120, name => $res['mws_model'],url => 'dashboard.php?asin='.$res['product_sku'].'&region=us', target => "FrameContent");
  $arr_can[] = array(id => 220, name => $res['mws_model'],url => 'dashboard.php?asin='.$res['product_sku'].'&region=ca', target => "FrameContent");
  $arr_uk[] = array(id => 320, name => $res['mws_model'],url => 'dashboard.php?asin='.$res['product_sku'].'&region=uk', target => "FrameContent");
}
  $json_arr = json_encode($arr_us);
  $json_can_arr = json_encode($arr_can);
  $json_uk_arr = json_encode($arr_uk);

mysqli_close($mysqli); 
?>
<div>
  <ul id="tree" class="ztree" style="width:260px; overflow:auto;"></ul>
</div>
<link rel="stylesheet" href="css/zTreeStyle/zTreeStyle.css" type="text/css">
<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
<script type="text/javascript" src="js/jquery.ztree.core.min.js"></script>
<script type="text/javascript">
    var zTree;
    var demoIframe;

    var setting = {
      view: {
        dblClickExpand: false,
        showLine: true,
        selectedMulti: false
      },
      data: {
        simpleData: {
          enable: true,
          idKey: "id",
          pIdKey: "pId",
          rootPId: ""
        }
      },
      callback: {
        beforeClick: function (treeId, treeNode) {
          var zTree = $.fn.zTree.getZTreeObj("tree");
          if (treeNode.isParent) {
            zTree.expandNode(treeNode);
            return false;
          } else {
            // demoIframe.attr("src", treeNode.file + ".html");
            return true;
          }
        }
      }
    };

    var zNodes = [
      {id: 1, pId: 0, name: "Amazon.COM KPI", open: false},
      {id: 101, pId: 1, name: "US BSR(top 30)", url: "dashboard.php?list=top30&region=us", target:"FrameContent"},
      {id: 102, pId: 1, name: "SKUs by Owner",children: [{id: 103, name: "Carole", url: "dashboard.php?owner=Carole&region=us", target:"FrameContent"},{id: 104, name: "Eric", url: "dashboard.php?owner=Eric&region=us", target:"FrameContent"},{id: 105, name: "Joy", url: "dashboard.php?owner=Joy&region=us", target:"FrameContent"},{id: 106, name: "Sonny", url: "dashboard.php?owner=Sonny&region=us", target:"FrameContent"}]},
      {id: 119, pId: 1, name: "SKUs by Group",children:  <?php echo $json_arr; ?>},
      {id: 107, pId: 1, name: "US BSR(weekly)", url: "dashboard.php?data=weekly&region=us", target:"FrameContent"},
      {id: 108, pId: 1, name: "US BSR Groups", url: "editor.php?file=amazon.us_asin_sku_competitors.txt", target:"FrameContent"},
      // {id: 109, pId: 1, name: "ASIN_SKU", url: "#", target:"FrameContent"},
      // {id: 110, pId: 1, name: "ASIN_SKU_TITLE", url: "dashboard.php?list=ASIN_SKU_TITLE", target:"FrameContent"},
      // {id: 111, pId: 1, name: "Keywords Search Rank (KSR)", url: "dashboard.php?list=Keywords Search Rank", target:"FrameContent"},
      // {id: 112, pId: 1, name: "LinkList Editor", url: "dashboard.php?list=LinkList Editor", target:"FrameContent"},
      // {id: 113, pId: 1, name: "Traffics", url: "dashboard.php?list=Traffics", target:"FrameContent"},
      // {id: 112, pId: 1, name: "Gigs Performance", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 114, pId: 1, name: "Reviews Target", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 115, pId: 1, name: "iSpring Reviews", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 116, pId: 1, name: "Competitors Reviews", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 117, pId: 1, name: "Competitors Best Sellers", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 118, pId: 1, name: "Offer Monitor", url: "dashboard.php?list=top30", target:"FrameContent"},
      
      // {id: 2, pId: 0, name: "AmazonCAN KPI", open: false},
      // {id: 201, pId: 2, name: "CAN Best Sellers Rank (BSR)", url: "dashboard.php?region=ca", target:"FrameContent"},
      // {id: 206, pId: 2, name: "CAN BSR Groups", url: "#", target:"FrameContent"},

      {id: 2, pId: 0, name: "Amazon.CAN KPI", open: false},
      {id: 201, pId: 2, name: "CAN BSR(top 30)", url: "dashboard.php?list=top30&region=ca", target:"FrameContent"},
      {id: 202, pId: 2, name: "SKUs by Owner",children: [{id: 203, name: "Carole", url: "dashboard.php?owner=Carole&region=ca", target:"FrameContent"},{id: 204, name: "Eric", url: "dashboard.php?owner=Eric&region=ca", target:"FrameContent"},{id: 205, name: "Joy", url: "dashboard.php?owner=Joy&region=ca", target:"FrameContent"},{id: 206, name: "Sonny", url: "dashboard.php?owner=Sonny&region=ca", target:"FrameContent"}]},
      {id: 219, pId: 2, name: "SKUs by Group",children:  <?php echo $json_can_arr; ?>},
      {id: 207, pId: 2, name: "CAN BSR(weekly)", url: "dashboard.php?data=weekly&region=ca", target:"FrameContent"},
      {id: 208, pId: 2, name: "CAN BSR Groups", url: "editor.php?file=amazon.can_asin_sku_competitors.txt", target:"FrameContent"},
      
      // {id: 3, pId: 0, name: "AmazonUK KPI", open: false},
      // {id: 301, pId: 3, name: "UK Best Sellers Rank (BSR)", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 302, pId: 3, name: "UK BSR Groups", url: "#", target:"FrameContent"},
      {id: 3, pId: 0, name: "Amazon.UK KPI", open: false},
      {id: 301, pId: 3, name: "UK BSR(top 30)", url: "dashboard.php?list=top30&region=uk", target:"FrameContent"},
      {id: 302, pId: 3, name: "SKUs by Owner",children: [{id: 303, name: "Carole", url: "dashboard.php?owner=Carole&region=uk", target:"FrameContent"},{id: 304, name: "Eric", url: "dashboard.php?owner=Eric&region=uk", target:"FrameContent"},{id: 305, name: "Joy", url: "dashboard.php?owner=Joy&region=uk", target:"FrameContent"},{id: 306, name: "Sonny", url: "dashboard.php?owner=Sonny&region=uk", target:"FrameContent"}]},
      {id: 319, pId: 3, name: "SKUs by Group",children:  <?php echo $json_uk_arr; ?>},
      {id: 307, pId: 3, name: "UK BSR(weekly)", url: "dashboard.php?data=weekly&region=uk", target:"FrameContent"},
      {id: 308, pId: 3, name: "UK BSR Groups", url: "editor.php?file=amazon.uk_asin_sku_competitors.txt", target:"FrameContent"},
      
      // {id: 303, pId: 3, name: "EU LinkList Editor", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 304, pId: 3, name: "Traffics", url: "dashboard.php?list=top30", target:"FrameContent"},
      // {id: 305, pId: 3, name: "Gigs Performance", url: "dashboard.php?list=top30", target:"FrameContent"}
    ];

    $(document).ready(function () {
      var t = $("#tree");
      t = $.fn.zTree.init(t, setting, zNodes);
      var zTree = $.fn.zTree.getZTreeObj("tree");
      zTree.selectNode(zTree.getNodeByParam("id", 101));

    });

    //-->
  </script>
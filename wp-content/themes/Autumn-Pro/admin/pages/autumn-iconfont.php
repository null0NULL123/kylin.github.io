<?php
function wpjam_autumn_iconfont_page(){
?>

<iframe src="<?php echo get_bloginfo('template_directory');?>/static/fonts/demo.html" id="iconfont-iframe" style="width:100%;"></iframe>

		<script type="text/javascript">
			jQuery(function($){
				$(function(){
					changeWH();
		 		}); 
			    function changeWH(){ 
			        $("#iconfont-iframe").height($(document).height());
			        //$("#iconfont-iframe").width($(document).width());
			    } 
			    window.onresize=function(){  
			         changeWH();   
			    } 
			});
		</script>

<?php }?>
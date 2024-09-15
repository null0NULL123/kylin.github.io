<div class="user-post col-lg-9">
	<div class="rownone">
    <h3 class="section-title"><span>我的文章</span></h3>
    <?php 
    if(have_posts()){
    	while(have_posts()){ the_post(); 
    		get_template_part('template-parts/content-list');
    	}
    	get_template_part('template-parts/paging'); 
    }else{
        echo '<p>您还没有发布文章</p>';
    } 
    ?>  
    </div>
   
</div>

<?php
if($action == 'topic'){
    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        
        $title          = $_POST['topic_title'];
        $raw_content    = $_POST['topic_content'];
        $group_id       = $_POST['group_id'];

        $images         = [];

        for ($i=1; $i <= 3; $i++) { 
            if(isset($_FILES['image'.$i]) && $_FILES['image'.$i]['name'] && $_FILES['image'.$i]['error'] == 0){
                $upload_file    = wp_handle_upload($_FILES['image'.$i], ['test_form'=>false]);  

                if(empty($upload_file['error'])){
                     $images[]  = $upload_file['url'];
                }
            }
        }

        $data   = compact('title', 'raw_content', 'group_id', 'images');

        $topic_id = WPJAM_Topic::insert($data);

        if(is_wp_error($topic_id)){
            $errors     = $topic_id;
        }else{
            wp_safe_redirect(home_url('topic/'.$topic_id));
            exit;
        }
    }
}

$action_file = get_template_directory().'/user/action/'.$action.'.php'; 

if(!is_file($action_file)){
    include(get_template_directory().'/404.php');
    exit;
}

get_header();
?>

<div class="user site-content container">
    <div class="row">

        <div class="col-lg-3">
            <aside class="user-widget widget-area">

                <div class="sidebar-header header-cover" style="background-image: url(<?php $login_bg_img = wpjam_theme_get_setting('login_bg_img') ?: get_template_directory_uri().'/static/images/login_bg_img.jpg'; echo $login_bg_img;?>);">
                    <div class="sidebar-image">
                        <img src="<?php echo get_avatar_url(get_current_user_id());?>">
                        <a style="font-size: 18px;font-weight: 700;color: #fff;" href="<?php echo home_url(user_trailingslashit('/user'));?>"><?php echo $current_user->nickname;?></a>
                    </div>
                    <p class="sidebar-brand"><?php if( $current_user->description ){ echo $current_user->description;}else{ echo '我还没有学会写个人说明！'; }?></p>
                </div>

                <section class="widget widget_categories">
                    <h5 class="widget-title">用户中心</h5>
                    <ul>

                        <?php if( current_user_can( 'manage_options' ) ) {?>
                            <li><a href="<?php echo home_url(user_trailingslashit('/wp-admin')); ?>"><span class="iconfont icon-yibiaopan1"></span> 进入后台</a></li>
                        <?php }?>
                        <?php if( !wpjam_theme_get_setting('subscriber_fw') || !current_user_can('subscriber') ){?>
                        <li><a<?php if($action == 'contribute') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/contribute')); ?>"><span class="iconfont icon-ykq_tab_tougao"></span> 文章投稿</a><?php if($action == 'contribute') echo '<i></i>';?></li>
                        <?php }?>
                        <?php if(wpjam_topic_get_setting('add_4_theme')) {?>
                            <?php if( !wpjam_theme_get_setting('subscriber_ft') || !current_user_can('subscriber') ){?>
                            <li><a<?php if($action == 'topic') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/topic')); ?>"><span class="iconfont icon-fatieliang" style="font-size: 17px;vertical-align: top;"></span> 发布帖子</a><?php if($action == 'topic') echo '<i></i>';?></li>
                            <?php }?>
                        <?php }?>
                        <li><a<?php if($action == 'posts') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/posts')); ?>"><span class="iconfont icon-wenzhang"></span> 我的文章</a><?php if($action == 'posts') echo '<i></i>';?></li>
                        <?php if(wpjam_theme_get_setting('single_fav')){?>
                        <li><a<?php if($action == 'collection') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/collection')); ?>"><span class="iconfont icon-collection"></span> 我的收藏</a><?php if($action == 'collection') echo '<i></i>';?></li>
                        <?php }?>
                        <li><a<?php if($action == 'comments') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/comments')); ?>"><span class="iconfont icon-pinglun"></span> 我的评论</a><?php if($action == 'comments') echo '<i></i>';?></li>
                        <li><a<?php if($action == 'profile') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/profile')); ?>"><span class="iconfont icon-zhanghaoxinxi"></span> 账号信息</a><?php if($action == 'profile') echo '<i></i>';?></li>
                        <?php $bind_actions = wpjam_get_login_actions('bind'); if($bind_actions){ ?>
                        <li><a<?php if($action == 'bind') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/bind')); ?>"><span class="iconfont icon-renyuanbangding"></span> 账号绑定</a><?php if($action == 'bind') echo '<i></i>';?></li>
                        <?php }?>
                        <li><a<?php if($action == 'password') echo ' style="color:var(--accent-color)"';?> href="<?php echo home_url(user_trailingslashit('/user/password')); ?>"><span class="iconfont icon-xiugaimima"></span> 修改密码</a><?php if($action == 'password') echo '<i></i>';?></li>
                        <li><a href="<?php echo wp_logout_url( home_url() ); ?>"><span class="iconfont icon-tuichudenglu"></span> 退出登录</a></li>
                    </ul>
                </section>
            </aside>
        </div>

        <?php  include($action_file);?>

       </div>
    </div>
<?php get_footer();?>
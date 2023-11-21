<?php
$availableValues = array("媽祖", "觀世音", "土地公");
//  $avaliableServices = ['點燈','法會','安太歲','疏文'];
$temple_page = home_url().'/wp-admin/admin.php?page=custom-list';
?>
<h4 class="title_add">新增廟宇</h4>
<div class="temple_add">
    <div class="block_add">
        <label class="label_add">名稱:</label>
        <input id="name" type="text">
    </div>
    <div class="block_add">
        <label class="label_add">主神:</label>
        <?php
            foreach ($availableValues as $value) {
                echo '<input type="checkbox" name="gods[]" value="' . $value . '" > ' . $value ;
            }
        ?>
    </div>
    <div class="block_add">
        <label class="label_add">地址:</label>
        <input id="address" type="text">
    </div>
    <div class="block_add">
        <label class="label_add">電話:</label>
        <input id="phone" type="text">
    </div>
    <div class="block_add">
        <label class="label_add">主圖網址:</label>
        <button type="button" id="activeImage" class="button" data-editor="excerpt"><span class="wp-media-buttons-icon"></span> 新增媒體</button>
        <input type="text" id="editor_image" readonly>
    </div >
    <div class="block_add">
        <label class="label_add info">簡介:</label>
        <textarea name="" id="info" cols="30" rows="5"></textarea>
    </div>
    <div class="block_add">
        <label class="label_add">服務:</label>
    </div>
   <div class="block_add">
        <label class="label_add">點燈內容:</label>
        <textarea name="" id="light_content" cols="30" rows="5"></textarea>
    </div>
    <div class="block_add">
        <label class="label_add">點燈商品:</label>
        <?php
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'product_cat' => 'light',
            );
            $products = new WP_Query($args);
            if ($products->have_posts()) :
                ?>
                <select id="light-products" name="light-products">
                    <option value="">請選擇</option>
                    <?php while ($products->have_posts()) : $products->the_post(); ?>
                        <option value="<?php the_ID(); ?>"><?php the_title(); ?></option>
                    <?php endwhile; ?>
                </select>
                <?php
                wp_reset_postdata();
            else :
                echo '沒有點燈類別的商品。';
            endif;
        ?>
        <input type="text" id="light_product">
    </div>
    <div class="block_add">
        <label class="label_add">疏文內容:</label>
        <textarea name="" id="shuwen_content" cols="30" rows="5"></textarea>
    </div>
    <div class="block_add">
        <label class="label_add">疏文商品:</label>
        <?php
            $args = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'product_cat' => 'shuwen',
            );
            $products = new WP_Query($args);
            if ($products->have_posts()) :
                ?>
                <select id="shuwen-products" name="shuwen-products">
                    <option value="">請選擇</option>
                    <?php while ($products->have_posts()) : $products->the_post(); ?>
                        <option value="<?php the_ID(); ?>"><?php the_title(); ?></option>
                    <?php endwhile; ?>
                </select>
                <?php
                wp_reset_postdata();
            else :
                echo '沒有疏文類別的商品。';
            endif;
        ?>
        <input type="text" id="shuwen_product">
    </div>
    <div class="block_add">
        <label class="label_add">直播iframe:</label>
        <input id="live_iframe" type="text">
    </div>
    <div class="block_add">
       <button class="btn_add">送出</button>
    </div>
</div>
<script>
var temple_page = '<?php echo $temple_page; ?>';
// open media folder
jQuery(document).ready(function($) {
    $(document).on('click','#activeImage', function(){
		var file_frame = wp.media({
			title:'選擇活動圖片',
			button:{
				text:'使用',
			},
			multiple:false
		});
		file_frame.on('open',function(){
			var selection = file_frame.state().get('selection');
			id = $('#editor_image').val();
			attachment = wp.media.attachment(id);
			selection.add(attachment?[attachment]:[]);
		});
		file_frame.on('select',function(){
			var attachment = file_frame.state().get('selection').first().toJSON();
			$('#editor_image').parent().find('img').attr('src',attachment.url);
			$('#editor_image').val(attachment.id);
		});
		file_frame.open();
	});
});
//添加點燈商品
var selectElement = document.getElementById('light-products');
var lightProductInput = document.getElementById('light_product');

selectElement.addEventListener('change', function() {
    var selectedOption = selectElement.options[selectElement.selectedIndex];
    var selectedProductId = selectedOption.value;
    if (lightProductInput.value !== '') {
        lightProductInput.value += ',' + selectedProductId;
    } else {
        lightProductInput.value = selectedProductId;
    }
});

//添加疏文商品
var shuwenElement = document.getElementById('shuwen-products');
var shuwenProductInput = document.getElementById('shuwen_product');

shuwenElement.addEventListener('change', function() {
    var selectedOption = shuwenElement.options[shuwenElement.selectedIndex];
    var selectedProductId = selectedOption.value;
    if (shuwenProductInput.value !== '') {
        shuwenProductInput.value += ',' + selectedProductId;
    } else {
        shuwenProductInput.value = selectedProductId;
    }
});


//送出
const btn = document.querySelector('.btn_add');
const name = document.querySelector('#name')
const main_god = document.querySelector('#main_god')
const address = document.querySelector('#address')
const phone = document.querySelector('#phone')
const image_url = document.querySelector('#editor_image')
const info = document.querySelector('#info')
const live_iframe = document.querySelector('#live_iframe')
const light_content = document.querySelector('#light_content')
const shuwen_content = document.querySelector('#shuwen_content')

btn.addEventListener('click', () => {
    const checkboxes = document.querySelectorAll('input[name="gods[]"]:checked');
    const selectedGods = [];
    checkboxes.forEach(checkbox => {
        selectedGods.push(checkbox.value);
    });
    const formData = new FormData();
    formData.append('action', 'add_temple');
    formData.append('name', name.value); 
    formData.append('main_god', selectedGods);
    formData.append('address', address.value);
    formData.append('phone', phone.value);
    formData.append('image_url', image_url.value);
    formData.append('info', info.value);
    formData.append('light_content', light_content.value);
    formData.append('light_products', lightProductInput.value);
    formData.append('shuwen_content', shuwen_content.value);
    formData.append('shuwen_products', shuwenProductInput.value);
    formData.append('live_iframe', live_iframe.value);

    fetch(ajaxurl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin' 
    })
    .then(response => response.json())
    .then(data => {
        if(data.success){
            window.location.href = temple_page
        }
    })
    .catch(error => {
        console.error(error);
    });
});
</script>
<style>
    #activeImage,#light-products,#shuwen-products{
        margin-right:10px;
    }
    .title_add{
        font-size:24px;
    }
    .temple_add{
        display:flex;
        flex-direction:column;
        gap:10px;
    }
    .label_add{
        width: 80px;
    }
    .block_add{
        display:flex;
        align-items:center;
    }
    .btn_add{
        margin-top:10px;
        width: 100px;
        color: #fff;
        background-color: #007bff;
        border-color: #007bff;
        display: inline-block;
        font-weight: 400;
        text-align: center;
        white-space: nowrap;
        vertical-align: middle;
        user-select: none;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
    }
</style>
<?php
if (isset($_GET['id'])) {
    $temple_id = intval($_GET['id']); 
    
    $temple_data = get_temple_data_by_id($temple_id); 

    if ($temple_data) {
        $name = $temple_data['name'];
        $main_god = $temple_data['main_god'];
        $address = $temple_data['address'];
        $phone = $temple_data['phone'];
        $image_url = $temple_data['image_url'];
        $info = $temple_data['info'];
        $service = $temple_data['service'];
        $live_iframe = $temple_data['live_iframe'];
        $light_content = $temple_data['light_content'];
        $light_products = $temple_data['light_products'];
        $shuwen_content = $temple_data['shuwen_content'];
        $shuwen_products = $temple_data['shuwen_products'];
        $selectedValuesArray = explode(',', $main_god);
        $availableValues = array("媽祖", "觀世音", "土地公");
    }
}
function get_temple_data_by_id($temple_id) {
    global $wpdb;
    $table_name = 'gc_temple'; 
    
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $temple_id);
    $temple_data = $wpdb->get_row($query, ARRAY_A); 

    return $temple_data;
}    
$temple_page = home_url().'/wp-admin/admin.php?page=custom-list';
?>
<h4 class="title_add">編輯</h4>
<div class="temple_add">
    <input id="temple_id" type="hidden" name="temple_id" value="<?php echo $temple_id ?>">
    <div class="block_add">
        <label class="label_add">名稱:</label>
        <input id="name" type="text" value="<?php echo isset($name) ? esc_attr($name) : ''; ?>">
    </div>
    <div class="block_add">
        <label class="label_add">主神:</label>
        <?php
            foreach ($availableValues as $value) {
                $isChecked = in_array($value, $selectedValuesArray) ? 'checked' : '';
                echo '<input type="checkbox" name="gods[]" value="' . $value . '" ' . $isChecked . '> ' . $value . '<br>';
            }
        ?>
    </div>
    <div class="block_add">
        <label class="label_add">地址:</label>
        <input id="address" type="text" value="<?php echo isset($address) ? esc_attr($address) : ''; ?>">
    </div>
    <div class="block_add">
        <label class="label_add">電話:</label>
        <input id="phone" type="text" value="<?php echo isset($phone) ? esc_attr($phone) : ''; ?>">
    </div>
    <div class="block_add">
        <label class="label_add">主圖網址:</label>
        <button type="button" id="activeImage" class="button" data-editor="excerpt"><span class="wp-media-buttons-icon"></span> 新增媒體</button>
        <input type="text" id="editor_image" readonly value="<?php echo isset($image_url) ? esc_attr($image_url) : ''; ?>">
    </div >
    <div class="block_add">
        <label class="label_add">簡介:</label>
        <textarea name="" id="info" cols="30" rows="5"><?php echo isset($info) ? esc_attr($info) : ''; ?></textarea>
    </div>
    <div class="block_add">
        <label class="label_add">服務:</label>
    </div>
    <div class="block_add">
        <label class="label_add">點燈內容:</label>
        <textarea name="" id="light_content" cols="30" rows="5"><?php echo isset($light_content) ? esc_attr($light_content) : ''; ?></textarea>
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
                echo '沒有 "light" 類別的商品。';
            endif;
        ?>
        <input type="text" readonly id="light_product" value="<?php echo isset($light_products) ? esc_attr($light_products) : ''; ?>">
        <button class="delete_btn">清空</button>
    </div>
    <div class="block_add">
        <label class="label_add">疏文內容:</label>
        <textarea name="" id="shuwen_content" cols="30" rows="5"><?php echo isset($shuwen_content) ? esc_attr($shuwen_content) : ''; ?></textarea>
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
                echo '沒有 "shuwen" 類別的商品。';
            endif;
        ?>
        <input readonly type="text" id="shuwen_product" value="<?php echo isset($shuwen_products) ? esc_attr($shuwen_products) : ''; ?>">
        <button class="delete_btn">清空</button>
    </div>
    <div class="block_add">
        <label class="label_add">直播iframe:</label>
        <input id="live_iframe" type="text" value="<?php echo isset($live_iframe) ? esc_attr($live_iframe) : ''; ?>">
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
            console.log(123);
			var selection = file_frame.state().get('selection');
			id = $('#editor_image').val();
			attachment = wp.media.attachment(id);
			selection.add(attachment?[attachment]:[]);
		});
		file_frame.on('select',function(){
            console.log(456);
			var attachment = file_frame.state().get('selection').first().toJSON();
			$('#editor_image').parent().find('img').attr('src',attachment.url);
			$('#editor_image').val(attachment.id);
		});
		file_frame.open();
	});
});
//清空商品
const deleteBtns = document.querySelectorAll('.delete_btn')
deleteBtns.forEach((btn) =>{
    btn.addEventListener('click',(e)=>{
        e.target.previousElementSibling.value = ''
    })
})

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


const temple_id = document.querySelector('#temple_id')
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
    formData.append('temple_id',temple_id.value)
    formData.append('action', 'edit_temple');
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
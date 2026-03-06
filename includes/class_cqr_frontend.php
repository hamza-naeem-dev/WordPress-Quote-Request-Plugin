<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CQR Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" 
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" 
integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>


<?php 
if(!defined("ABSPATH"))
    {
        exit;
    }

class CQR_Frontend
{
    public function __construct()
    {
        add_shortcode("cqr_quote_form", array($this, "cqr_render_form"));
        add_action("init", array($this, "cqr_form_submission"));
        add_action("init", array($this, "register_quote_request_post"));
        add_shortcode("cqr_display_quotes", array($this, "display_requested_qoutes"));
    }

    public function cqr_render_form()
    {
        if(isset($_GET["cqr_success"]))
            {
                echo "<p style= 'color:green;'> Your quote has been sent successfully! </p>";
            }
        ob_start(); ?>
        <div class="d-flex justify-content-center align-items-center">

        
            <form class="p-4 mb-2 bg-success text-white rounded" action="" method="post">
                <p>
                    <label class="fw-semibold">Name</label><br/>
                    <input class="border border-primary border border-4" type="text" name="cqr_name" required>
                </p>

                <p>
                    <label class="fw-semibold">Email</label><br/>
                    <input class="border border-primary border border-4" type="email" name="cqr_email" required>
                </p>

                <p>
                    <label class="fw-semibold">Message</label><br/>
                    <textarea class="border border-primary border border-4" name="cqr_message" required></textarea>
                </p>

                <?php wp_nonce_field("cqr_form_submit_action", "cqr_nonce") ?>

                <p class="d-flex justify-content-center"><input class="border border-warning border border-3 text-primary rounded-top" type="submit" name="cqr_submit" value="Request Quote"></p>
            </form>
        </div>

    <?php

        return ob_get_clean();
    }

    public function cqr_form_submission()
    {
        if(is_admin())
            {
                return;
            }
        if(isset($_POST["cqr_submit"]))
            {
                if(!isset($_POST["cqr_nonce"]) || ! wp_verify_nonce($_POST["cqr_nonce"], "cqr_form_submit_action"))
                    {
                        return;
                    }

                $name = sanitize_text_field($_POST["cqr_name"]);
                $email = sanitize_email($_POST["cqr_email"]);
                $message = sanitize_textarea_field($_POST["cqr_message"]);

                //Creating new post

                $new_post = array('post_title' => "Quote Request From: ".$name,
                'post_content'=> "Email: ".$email . '<br/> Message: '.$message,
                'post_status' => "publish",
                'post_type' => "quote_request",
                'meta_input'   => array( // Store the name and email as post metadata
                '_quote_request_name'  => $name,
                '_quote_request_email' => $email));

                $post_id = wp_insert_post($new_post);

                if($post_id)
                    {
                        wp_mail(get_option("admin_email"), "New Quote Request", "Name: $name\nEmail: $email\nMessage: $message");
                        if(!isset($_GET["cqr_success"]))
                        {
                            wp_redirect(add_query_arg('cqr_success', '1', wp_get_referer()));
                            exit;
                        }
                    }
            }
        
    }

    public function register_quote_request_post() //Custom Post Request
    {
        $labels = array('name' => 'Quote Requests',
        'singular_name'      => 'Quote Request',
        'menu_name'          => 'Quote Requests',
        'name_admin_bar'     => 'Quote Request',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Quote Request',
        'new_item'           => 'New Quote Request',
        'edit_item'          => 'Edit Quote Request',
        'view_item'          => 'View Quote Request',
        'all_items'          => 'All Quote Requests',
        'search_items'       => 'Search Quote Requests',
        'not_found'          => 'No quote requests found.',
        'not_found_in_trash' => 'No quote requests found in Trash.',
        'featured_image'     => 'Customer Image',
        'set_featured_image' => 'Set customer image',
        'remove_featured_image' => 'Remove customer image',
        'use_featured_image' => 'Use as customer image',
        'archives'           => 'Quote Request Archives',
        'insert_into_item'   => 'Insert into quote request',
        'uploaded_to_this_item' => 'Uploaded to this quote request',);

        $args = array('labels'  => $labels,
        'public'             => true,
        'has_archive'        => true,
        'rewrite'            => array('slug' => 'quote-requests'),
        'show_in_rest'       => true, // Enable Gutenberg editor
        'supports'           => array('title', 'editor', 'author', 'custom-fields'),
        'menu_icon'          => 'dashicons-feedback',);

        register_post_type("quote_request", $args);
    }

    public function display_requested_qoutes()
    {
        $quote_list = new WP_Query(array("post_type" => "quote_request",
                                            "post_status" => "publish"));
        
        if($quote_list -> have_posts())
            {
                while($quote_list -> have_posts())
                    {
                        $quote_list -> the_post();
                        echo '<div class=quote_items bg-primary p-3 mt-3">';
                            echo "<h3>" . esc_html(get_the_title()) . "<h3>";
                            echo "<p>" . wp_kses_post(get_the_content()) . "<p>";
                        echo '</div>';
                    }
            }
    }
}


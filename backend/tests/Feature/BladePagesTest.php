<?php

namespace Tests\Feature;

use Tests\TestCase;

class BladePagesTest extends TestCase
{
    public function test_home_page_renders(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSeeText('FreightFlow');
        $response->assertSeeText('Ship globally with confidence');
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSeeText('Operations authentication');
    }

    public function test_dashboard_redirects_to_tracking_when_not_authenticated(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/tracking');
    }

    public function test_public_pages_render(): void
    {
        $this->get('/about')->assertOk()->assertSeeText('About FreightFlow');
        $this->get('/services')->assertOk()->assertSeeText('Enterprise services');
        $this->get('/pricing')->assertOk()->assertSeeText('Pricing');
        $this->get('/contact')->assertOk()->assertSeeText('Contact');
        $this->get('/support')->assertOk()->assertSeeText('Support');
        $this->get('/rates')->assertOk()->assertSeeText('Rates');
        $this->get('/register')->assertRedirect('/tracking');
        $this->get('/forgot-password')->assertRedirect('/tracking');
        $this->get('/careers')->assertOk()->assertSeeText('Careers at FreightFlow');
        $this->get('/blog')->assertOk()->assertSeeText('Logistics insights');
        $this->get('/faq')->assertOk()->assertSeeText('Frequently asked questions');
        $this->get('/shipping')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk()->assertSeeText('Operations authentication');
        $this->get('/staff')->assertRedirect('/login');
        $this->get('/driver')->assertRedirect('/login');
        $this->get('/warehouse')->assertRedirect('/login');
    }
}

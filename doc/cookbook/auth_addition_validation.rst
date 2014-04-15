auth_addition_validation.rst

Create addition authenticating validation (like Captcha)
============================

Create a listener class

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Security\Core\Exception\AuthenticationException;
    use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
    
    class CaptchaListener extends UsernamePasswordFormAuthenticationListener
    {
        /**
         * {@inheritdoc}
         */
        protected function attemptAuthentication(Request $request)
        {
            $captcha = $request->get('captcha', null, true);
            
            // your validating logic
            if ($captcha != 'secret captcha code') {
                throw new AuthenticationException('Captcha error');
            }
    
            return parent::attemptAuthentication($request);
        }
    }

and add `listener_class` to security form option

    $app['security.firewalls'] = array(
        'main' => array(
            'form' => array(
                'listener_class' => 'CaptchaListener',
            ),
        )
    );

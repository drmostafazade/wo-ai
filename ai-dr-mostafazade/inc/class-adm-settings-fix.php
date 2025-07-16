    public function sanitize_options($input) {
        // Encrypt sensitive data
        if (!empty($input['arvan_config']['database']['user'])) {
            $arvan = new ADM_ArvanCloud();
            $input['arvan_config']['database']['user'] = $arvan->encrypt($input['arvan_config']['database']['user']);
            $input['arvan_config']['database']['pass'] = $arvan->encrypt($input['arvan_config']['database']['pass']);
            
            if (!empty($input['arvan_config']['redis']['auth'])) {
                $input['arvan_config']['redis']['auth'] = $arvan->encrypt($input['arvan_config']['redis']['auth']);
            }
        }
        
        return $input;
    }

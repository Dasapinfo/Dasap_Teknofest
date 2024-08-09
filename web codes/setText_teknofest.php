<?php

    header('Content-Type: text/plain');

    $Model = $_REQUEST['model'];
    $Text = $_REQUEST['text'];

	if ($Model == '1') { // BERT Base
        $url_disaster = "https://api-inference.huggingface.co/models/dbmdz/convbert-base-turkish-mc4-uncased";    
        $url_req = "https://api-inference.huggingface.co/models/dbmdz/convbert-base-turkish-mc4-uncased";    
    }
    else if ($Model == '2') { // BERT FT
        $url_disaster = "https://api-inference.huggingface.co/models/dasap/DASAP_Bert_Model";
        $url_req = "https://api-inference.huggingface.co/models/dasap/DASAP_Bert_REQ_Model";
    }
    else if ($Model == '3') { // RoBERTa Base
        $url_disaster = "https://api-inference.huggingface.co/models/burakaytan/roberta-base-turkish-uncased";    
        $url_req = "https://api-inference.huggingface.co/models/burakaytan/roberta-base-turkish-uncased";    
    }
    else if ($Model == '4') { // RoBERTa FT
        $url_disaster = "https://api-inference.huggingface.co/models/dasap/DASAP_roBERTA_Model";
        $url_req = "https://api-inference.huggingface.co/models/dasap/DASAP_roBERTA_REQ_Model";
    }
    
// ---------------- DISASTER CLASSIFICATION -----------------------------

    $prompt_disaster = "Aşağıda verilen cümleyi doğal afet türüne göre aşağıdaki adımları uygulayarak sınıflandır ve sadece sonucu yaz. Verilen cümle deprem doğal afetiyle ilgiliyse veya 'yardım', 'konum', 'kimlik', 'iletişim' gibi acil durum içeren bilgiler içeriyorsa 'Deprem' yaz. Verilen cümle sel doğal afetiyle ilgiliyse 'Sel' yaz. Verilen cümle yangın doğal afetiyle ilgiliyse 'Yangın' yaz. Verilen cümle gerçekten doğal afetle ilgili değilse veya mecazi anlamda doğal afetlerle ilişkilendirilebilirse 'Alakasız' olarak sınıflandır.";

	if ($Model == '1') { // BERT Base

        $prompt_disaster_text = $prompt_disaster . " Cümle: " . $Text . ". Bu cümlenin afet sınıfı şudur: [MASK]";          
		
		$postData = json_encode([
			"inputs" => $prompt_disaster_text  
		]);
    }
	else if ($Model == '3') { // RoBERTa Base

        $prompt_disaster_text = $prompt_disaster . " Cümle: " . $Text . ". Bu cümlenin afet sınıfı şudur: <mask>";          
		
		$postData = json_encode([
			"inputs" => $prompt_disaster_text  
		]);
    }
    else {

        $prompt_disaster_text = $Text;
		
		$postData = json_encode([
            "text" => $prompt_disaster_text 
        ]);
    }    
	
	#echo "postdata: " . $postData . "\n\r";

    $ch = curl_init($url_disaster);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer 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',
        'Content-Length: ' . strlen($postData)
    ]);

    $response = curl_exec($ch);
    
    //echo "Disaster response: " . $response . "\n\r\n\r";
    
    $responsearray = json_decode($response, true);
    
    $responseval = "Alakasız";

	if ($Model == '1' || $Model == '3') { // BERT Base or roBERTa Base
		$disastervalue = $responsearray[0]['token_str'];
		#echo "disastervalue: " . $disastervalue . "\n\r\n\r";
		$ResultArray["Disaster"] = $disastervalue;
	}
	else {
		$disastervalue = $responsearray[0]['label'];
    
		if ($disastervalue == "LABEL_1") {
			$responseval = "Deprem";
		}
		else if ($disastervalue == "LABEL_2") {
			$responseval = "Sel";
		}
		else if ($disastervalue == "LABEL_3") {
			$responseval = "Yangın";
		}
		// Check for errors
		if ($response === false) {
			$ResultArray["Disaster"] = curl_error($ch);
		} else {
			$ResultArray["Disaster"] = $responseval;
		}

		curl_close($ch);			
	}
	#echo "Disaster: " . $responseval . "\n\r\n\r";

    if ($responseval == "Alakasız") {
        $ResultArray["Requirement"] = "Yok";
    }
    else {

        // ---------------- REQUIREMENT CLASSIFICATION --------------------------

        if ($Model == '1' || $Model == '3') { // BERT Base or roBERTa Base

            $prompt_req = "
                    Aşağıda verilen cümleyi ihtiyaç türlerine göre aşağıdaki adımları uygulayarak sınıflandır ve sadece sonuçlarını yazdır. Verilen cümle herhangi bir arama kurtarma, destek ve yardım talebi içeriyorsa 'Arama - Kurtarma' yaz. Verilen cümle herhangi bir malzeme veya gıda yardım talebi içeriyorsa 'Malzeme - Yiyecek' yaz. Verilen cümle sadece haber içeriğindeyse 'Haber' yaz.
                ";			

            $prompt_req_text = $prompt_req . "\nCümle: " . $Text;    
        }
        else {

            $prompt_req_text = $Text;
        }

        $postData = json_encode([
            "text" => $prompt_req_text 
        ]);

        $ch = curl_init($url_req);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer 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',
            'Content-Length: ' . strlen($postData)
        ]);

        $response = curl_exec($ch);
        
        #echo "Requirement response: " . $response . "\n\r\n\r";
        
        $responsearray = json_decode($response, true);
        
        $disastervalue = $responsearray[0]['label'];
        
        $responseval = "Malzeme - Yiyecek";
        
        if ($disastervalue == "LABEL_0") {
            $responseval = "Arama - Kurtarma";
        }
        else if ($disastervalue == "LABEL_1") {
            $responseval = "Haber";
        }

        // Check for errors
        if ($response === false) {
            $ResultArray["Requirement"] = curl_error($ch);
        } else {
            $ResultArray["Requirement"] = $responseval;
        }

        curl_close($ch);
    }

    $ResultArray["Model"] = $Model;

	echo json_encode($ResultArray);

?>
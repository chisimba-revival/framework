<?php
/** Mobile adapter; context permissions and persistence remain owned by messaging. */
trait MessagingEndpoints
{
    private function messagingService() {
        $this->requireAuth();
        if(!$this->getObject('modules','modulecatalogue')->checkIfRegistered('messaging'))throw new NotFoundException('Messaging is not installed');
        header('Cache-Control: no-store');
        return $this->getObject('conversationservice','messaging');
    }
    private function messagingCall(callable $operation) {
        try{return $operation();}catch(DomainException $e){
            if($e->getMessage()==='unavailable')throw new AuthorizationException('Conversation access is unavailable');
            if($e->getMessage()==='conflict')throw new ConflictException('This submission ID already has different text');
            throw new ValidationException(['message'=>'Invalid conversation request']);
        }
    }
    private function messagingChannel($service,$context) {
        if(!is_string($context))throw new ValidationException(['context'=>'Select an authorised context']);
        $channel=$service->forContext($context);
        if(!$channel)throw new DomainException('unavailable');
        return $channel;
    }
    private function messagingPost(array $allowed) {
        $data=$this->getRequestData();
        if(strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json'||!empty($_FILES)||array_diff(array_keys($data),$allowed))throw new ValidationException(['message'=>'Use a JSON text request']);
        return $data;
    }
    public function GET_messaging_contexts(): void {
        $service=$this->messagingService();$policy=$this->getObject('conversationpolicy','messaging');$contexts=$this->getObject('dbcontext','context');
        $codes=$this->getObject('user','security')->isAdmin()?array_column($contexts->getAll(),'contextcode'):$this->getObject('usercontext','context')->getUserContext($this->currentUser['id']);
        $rows=[];
        foreach(array_unique($codes)as$code){
            if(!$policy->mayRead($code))continue;
            try{$channel=$service->forContext($code);}catch(DomainException $e){continue;}
            if($channel)$rows[]=['id'=>$code,'title'=>$contexts->getContextDetails($code)['title']];
        }
        $this->respond(['contexts'=>$rows,'contextLabel'=>$this->getObject('conversationview','messaging')->text('context')]);
    }
    public function GET_messaging_history(): void {
        $service=$this->messagingService();
        $data=$this->messagingCall(function()use($service){
            $channel=$this->messagingChannel($service,$this->getParam('context',''));
            $before=filter_var($this->getParam('before',0),FILTER_VALIDATE_INT);
            if($before===false)throw new DomainException('invalid');
            $result=$service->history($channel['id'],$before);
            foreach($result['messages']as&$row){$row['sender_name']=$this->getObject('user','security')->fullName($row['sender_id']);$row['sequence_no']=(int)$row['sequence_no'];}
            return $result;
        });$this->respond($data);
    }
    public function POST_messaging_messages(): void {
        $service=$this->messagingService();$data=$this->messagingPost(['context','submission_id','body','reply_to']);
        $row=$this->messagingCall(function()use($service,$data){$channel=$this->messagingChannel($service,$data['context']??null);return $service->send($channel['id'],$data['submission_id']??null,$data['body']??null,$data['reply_to']??'');});
        $this->respond(['id'=>$row['id'],'submission_id'=>$row['submission_id'],'sequence'=>(int)$row['sequence_no']]);
    }
    public function POST_messaging_read(): void {
        $service=$this->messagingService();$data=$this->messagingPost(['context','sequence']);
        $this->messagingCall(function()use($service,$data){$channel=$this->messagingChannel($service,$data['context']??null);$service->markRead($channel['id'],$data['sequence']??null);});$this->respond(['read'=>true]);
    }
}

<?php
/** Fixed authenticated adapter; the assessment module owns all domain access. @author Derek Keats */
trait SightsSoundsEndpoints
{
    private function ssCall(callable $operation): void {
        $this->requireAuth();
        if(!$this->getObject('modules','modulecatalogue')->checkIfRegistered('sightsandsounds'))throw new NotFoundException('Sights and Sounds is not installed');
        header('Cache-Control: no-store');
        try {$this->respond($operation($this->getObject('practiceexchange','sightsandsounds')));}
        catch(DomainException $e) {
            if($e->getMessage()==='denied')throw new AuthorizationException('Activity access unavailable');
            if($e->getMessage()==='missing')throw new NotFoundException('Activity unavailable');
            if($e->getMessage()==='stale')throw new ConflictException('This practice run changed. Keep your saved work and review it on the website.');
            throw new ValidationException(['message'=>'Invalid practice request']);
        }
    }
    private function ssString($value,int $length): string {
        if(!is_string($value)||$value===''||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$length)throw new ValidationException(['message'=>'Invalid identifier']);
        return $value;
    }
    private function ssPost(array $allowed): array {
        $data=$this->getRequestData();
        if(strtolower(trim(explode(';',$_SERVER['CONTENT_TYPE']??'')[0]))!=='application/json'||!empty($_FILES)||array_diff(array_keys($data),$allowed))throw new ValidationException(['message'=>'Use a JSON request']);
        return $data;
    }
    public function GET_sightsandsounds_catalogue(): void { $this->ssCall(fn($service)=>$service->catalogue()); }
    public function POST_sightsandsounds_package(): void {
        $this->ssCall(function($service){$data=$this->ssPost(['context','activity']);
            return $service->package($this->ssString($data['context']??null,255),$this->ssString($data['activity']??null,32));});
    }
    public function GET_sightsandsounds_media(): void {
        $this->ssCall(fn($service)=>$service->media($this->ssString($this->getParam('context'),255),$this->ssString($this->getParam('run'),32),$this->ssString($this->getParam('id'),32)));
    }
    public function POST_sightsandsounds_submit(): void {
        $this->ssCall(function($service){$data=$this->ssPost(['context','run','revision','answers']);
            if(!is_int($data['revision']??null)||$data['revision']<0||!is_array($data['answers']??null)||count($data['answers'])>50)throw new ValidationException(['message'=>'Invalid result']);
            return $service->submit($this->ssString($data['context']??null,255),$this->ssString($data['run']??null,32),$data['revision'],$data['answers']);});
    }
}

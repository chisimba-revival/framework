<?php
/**
 * Shared categories and tags, independent of any content editor.
 * Providers are registered by trusted module composition, never by request parameters.
 * Every association read/write asks the owning module for current access and scope.
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) die('No direct access');
class classificationservice extends ChisimbaObject
{
    private $store;
    private $providers = [];

    public function init()
    {
        $this->store = $this->getObject('classificationstore', 'classification');
    }

    /** Provider contract: classificationAccess(itemId) returns null or scope/read/edit. */
    public function registerProvider($module, $provider)
    {
        if (!is_string($module) || !preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $module)
            || !is_object($provider) || !is_callable([$provider,'classificationAccess'])) {
            throw new InvalidArgumentException('classification_invalid_provider');
        }
        if (isset($this->providers[$module]) && $this->providers[$module] !== $provider) {
            throw new LogicException('classification_provider_already_registered');
        }
        $this->providers[$module] = $provider;
    }

    /** Stable identity is independent of translated category labels. */
    public static function vocabulary($type, $scope, $kind)
    {
        if (!in_array($type, ['site','personal','context'], true)
            || !is_string($scope) || $scope === '' || strlen($scope)>64
            || preg_match('/[\x00-\x1f\x7f]/', $scope)
            || ($type === 'site' && $scope !== 'site')
            || !in_array($kind, ['category','tag'], true)) {
            throw new InvalidArgumentException('classification_invalid_scope');
        }
        return ['id'=>substr(hash('sha256', json_encode([$type,$scope,$kind])),0,32),
            'scope_type'=>$type,'scope_id'=>$scope,'kind'=>$kind];
    }

    /** Vocabulary management is distinct from permission to classify a particular item. */
    private function canManage(array $vocabulary)
    {
        $user = $this->getObject('user','security');
        if (!$user->isLoggedIn()) return false;
        if ($vocabulary['scope_type']==='context'
            && !$this->getObject('dbcontext','context')->getContextDetails($vocabulary['scope_id'])) return false;
        if ($user->isAdmin()) return true;
        if ($vocabulary['scope_type']==='personal') return (string)$user->userId()===$vocabulary['scope_id'];
        if ($vocabulary['scope_type']==='context') return $user->isCourseAdmin($vocabulary['scope_id']);
        $permissions = $this->getObject('permissionservice','security');
        $area = $permissions->areaIdForName('chisimba','classification');
        $right = $area ? $permissions->rightIdForArea($area,'manage') : null;
        return $right && $permissions->isGranted($user->userId(),$right);
    }

    private function requireManager(array $vocabulary)
    {
        if (!$this->canManage($vocabulary)) throw new DomainException('classification_forbidden');
    }

    /** Read the provider afresh: drafts, deletions and permission changes take immediate effect. */
    private function access($module, $item, $operation)
    {
        if (!isset($this->providers[$module]) || !is_string($item) || $item==='' || strlen($item)>64) {
            throw new DomainException('classification_forbidden');
        }
        $access = $this->providers[$module]->classificationAccess($item);
        if (!is_array($access) || ($access[$operation] ?? false) !== true) {
            throw new DomainException('classification_forbidden');
        }
        self::vocabulary($access['scope_type'] ?? null, $access['scope_id'] ?? null, 'tag');
        return $access;
    }

    /** Normalise labels and slugs without making translated labels identifiers. */
    private function normalise($name, $slug)
    {
        if (!is_string($name) || !is_string($slug)) throw new DomainException('classification_invalid_term');
        $name = trim(preg_replace('/\s+/u',' ', $name) ?? '');
        if ($name==='' || mb_strlen($name)>150 || preg_match('/[\x00-\x1f\x7f<>]/u',$name)) {
            throw new DomainException('classification_invalid_term');
        }
        if ($slug==='') $slug = trim(preg_replace('/[^\pL\pN_-]+/u','-',mb_strtolower($name)), '-');
        if ($slug==='' || mb_strlen($slug)>190 || !preg_match('/^[\pL\pN_-]+$/uD',$slug)) {
            throw new DomainException('classification_invalid_slug');
        }
        return [$name,mb_strtolower($slug)];
    }

    /** Upsert a term by stable ID; slug collisions require an explicit resolution. */
    private function writeTerm(array $vocabulary, $name, $slug, $parent, $id)
    {
        [$name,$slug] = $this->normalise($name,$slug);
        $terms = array_column($this->store->terms($vocabulary['id']), null, 'id');
        if ($id!=='' && !isset($terms[$id])) throw new DomainException('classification_unknown_term');
        if (!is_string($parent) || ($parent!=='' && !isset($terms[$parent]))
            || ($parent!=='' && $vocabulary['kind']==='tag')) throw new DomainException('classification_invalid_parent');
        foreach ($terms as $term) {
            if ($term['id']!==$id && ($term['slug']===$slug || mb_strtolower($term['name'])===mb_strtolower($name))) {
                throw new DomainException('classification_duplicate_term');
            }
        }
        $ancestor = $parent;
        $seen = [];
        while ($ancestor!=='') {
            if ($ancestor===$id || isset($seen[$ancestor])) throw new DomainException('classification_category_cycle');
            $seen[$ancestor] = true;
            $ancestor = $terms[$ancestor]['parent_id'];
        }
        $term = ['id'=>$id ?: bin2hex(random_bytes(16)), 'vocabulary_id'=>$vocabulary['id'],
            'name'=>$name,'slug'=>$slug,'parent_id'=>$parent];
        $this->store->saveTerm($term);
        return $term;
    }

    /** Managers can create/rename/reparent; changing a name does not change its ID or slug. */
    public function saveTerm($type, $scope, $kind, $name, $slug = '', $parent = '', $id = '')
    {
        $vocabulary = self::vocabulary($type,$scope,$kind);
        $this->requireManager($vocabulary);
        return $this->store->locked($vocabulary, function () use ($vocabulary,$name,$slug,$parent,$id) {
            if ($id!=='' && $slug==='') {
                foreach ($this->store->terms($vocabulary['id']) as $term) if ($term['id']===$id) $slug=$term['slug'];
            }
            return $this->writeTerm($vocabulary,$name,$slug,$parent,$id);
        });
    }

    public function deleteTerm($type, $scope, $kind, $id)
    {
        $vocabulary = self::vocabulary($type,$scope,$kind);
        $this->requireManager($vocabulary);
        $this->store->locked($vocabulary, function () use ($vocabulary,$id) {
            if (!in_array($id,array_column($this->store->terms($vocabulary['id']),'id'),true)) {
                throw new DomainException('classification_unknown_term');
            }
            $this->store->removeTerm($id);
        });
    }

    /** Complete vocabulary is available only to a manager or an authorised item editor. */
    public function managementTerms($type, $scope, $kind)
    {
        $vocabulary = self::vocabulary($type,$scope,$kind);
        $this->requireManager($vocabulary);
        return $this->store->terms($vocabulary['id']);
    }

    public function choices($module, $item, $kind)
    {
        $access = $this->access($module,$item,'edit');
        $vocabulary = self::vocabulary($access['scope_type'],$access['scope_id'],$kind);
        return $this->store->terms($vocabulary['id']);
    }

    /** Replace one kind only. Cross-scope and cross-kind IDs are rejected atomically. */
    public function assign($module, $item, $kind, array $ids)
    {
        $access = $this->access($module,$item,'edit');
        $vocabulary = self::vocabulary($access['scope_type'],$access['scope_id'],$kind);
        if (count($ids)>100 || count(array_filter($ids,'is_string'))!==count($ids)) {
            throw new DomainException('classification_invalid_terms');
        }
        $this->store->locked($vocabulary, function () use ($vocabulary,$module,$item,$ids) {
            $current = $this->access($module,$item,'edit');
            if ($current['scope_type']!==$vocabulary['scope_type'] || $current['scope_id']!==$vocabulary['scope_id']) {
                throw new DomainException('classification_scope_changed');
            }
            $available = array_column($this->store->terms($vocabulary['id']),'id');
            foreach ($ids as $id) if (!in_array($id,$available,true)) throw new DomainException('classification_unknown_term');
            $this->store->replaceLinks($vocabulary['id'],$module,$item,array_values(array_unique($ids)));
        });
    }

    /** Editors may introduce tags, but cannot introduce site categories by tagging content. */
    public function tag($module, $item, array $names)
    {
        $access = $this->access($module,$item,'edit');
        $vocabulary = self::vocabulary($access['scope_type'],$access['scope_id'],'tag');
        if (count($names)>100) throw new DomainException('classification_invalid_terms');
        $this->store->locked($vocabulary, function () use ($vocabulary,$module,$item,$names) {
            $current = $this->access($module,$item,'edit');
            if ($current['scope_type']!==$vocabulary['scope_type'] || $current['scope_id']!==$vocabulary['scope_id']) {
                throw new DomainException('classification_scope_changed');
            }
            $ids = [];
            foreach ($names as $name) {
                [$name,$slug] = $this->normalise($name,'');
                $found = null;
                foreach ($this->store->terms($vocabulary['id']) as $term) {
                    if (mb_strtolower($term['name'])===mb_strtolower($name)) $found=$term;
                }
                $term = $found ?? $this->writeTerm($vocabulary,$name,$slug,'','');
                $ids[] = $term['id'];
            }
            $this->store->replaceLinks($vocabulary['id'],$module,$item,array_values(array_unique($ids)));
        });
    }

    /** Readers get only terms attached to a record they can currently read. */
    public function forItem($module, $item, $kind, $editing = false)
    {
        $access = $this->access($module,$item,$editing ? 'edit' : 'read');
        $vocabulary = self::vocabulary($access['scope_type'],$access['scope_id'],$kind);
        return $this->store->itemTerms($vocabulary['id'],$module,$item);
    }

    /** Exact term browsing; no raw counts or unauthorised content identifiers escape. */
    public function browse($type, $scope, $kind, $term, $page = 1, $size = 20)
    {
        $vocabulary = self::vocabulary($type,$scope,$kind);
        if (!is_int($page) || $page<1 || $page>1000 || !is_int($size) || $size<1 || $size>100) {
            throw new InvalidArgumentException('classification_invalid_page');
        }
        $visible = []; $after = ''; $skip = ($page-1)*$size;
        do {
            $rows = $this->store->candidates($vocabulary['id'],$term,$after,200);
            foreach ($rows as $row) {
                $after = $row['id'];
                try { $access = $this->access($row['module_id'],$row['item_id'],'read'); }
                catch (DomainException $error) { continue; }
                if ($access['scope_type']!==$type || $access['scope_id']!==$scope) continue;
                if ($skip>0) { --$skip; continue; }
                $visible[] = ['module'=>$row['module_id'],'id'=>$row['item_id']];
                if (count($visible)===$size) return $visible;
            }
        } while (count($rows)===200);
        return $visible;
    }
}

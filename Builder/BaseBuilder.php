<?php

namespace Common\Builder {

    use Common\Core\Traits\DebugTrait;

    abstract class BaseBuilder
    {
        use DebugTrait;

        protected $callbacks = [];
        protected $schema = [];

        /**
         * @return array
         */
        public function build(): array
        {
            foreach ($this->callbacks as $callback) {
                $callback();
            }

            return $this->schema;
        }

        /**
         * @param \Closure $callback
         *
         * @return $this
         */
        protected function addCallback(\Closure $callback)
        {
            $this->callbacks[] = $callback;

            return $this;
        }
    }
}

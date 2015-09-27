<?php namespace DbSync\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Questioner
{

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var
     */
    private $input;

    /**
     * @var QuestionHelper
     */
    private $questionHelper;


    public function __construct(InputInterface $input, OutputInterface $output, QuestionHelper $questionHelper)
    {
        $this->output = $output;

        $this->input = $input;

        $this->questionHelper = $questionHelper;
    }

    public function confirm($question)
    {
        $question = new ConfirmationQuestion($question, false);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    public function question($question, $default = null)
    {
        $question = new Question($question, $default);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }

    public function secret($question, $default = null)
    {
        $question = new Question($question, $default);

        $question->setHidden(true);

        return $this->questionHelper->ask($this->input, $this->output, $question);
    }
}

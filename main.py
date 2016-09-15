# Import the necessary package to process data in JSON format
import tornado.ioloop
import tornado.web
from tornado.escape import json_encode
# Import the necessary methods from "twitter" library
from twitter import Twitter, OAuth, TwitterHTTPError, TwitterStream

"""
MainHandler consists of the code that hits on the twitter api\
fetches the tweets, consisting of #custserv
then the tweets are filtered on the basis of retweet_count, that\
to be greater than or equal to 1.
"""

class MainHandler(tornado.web.RequestHandler):
    def get(self):
        # Variables that contains the user credentials to access Twitter API 
        ACCESS_TOKEN = "482107777-irsRqZBYtDXeQ4umRkL6lQsF07aMe1YDT3HrUdug"
        ACCESS_SECRET = "5LGWjY0M8e3g7Z7SH9ceXHUgkPhMomNELhFlev2MXM6Oc"
        CONSUMER_KEY = 'klbbx9xjYm6tJxzUhutcmHSF2'
        CONSUMER_SECRET = 'V2VhVJWLSxZbdhDLLyQN1eRy7PMW0ooqgFFynjTkYa8a4B64GF'
        oauth = OAuth(ACCESS_TOKEN, ACCESS_SECRET, CONSUMER_KEY, CONSUMER_SECRET)
        twitter = Twitter(auth=oauth)
        tweets = twitter.search.tweets(q="#custserv")
        output = {
            'Author':'Shubhodeep Mukherjee',
            'Attribute':'Output with tweets having #custserv and minimum 1 retweet_count',
            'Response':[]
        }
        for tweet in tweets['statuses']:
            if(tweet['retweet_count']>0):
                output['Response'].append(tweet)
        self.write(output)

def make_app():
    return tornado.web.Application([
        (r"/", MainHandler),
    ])

if __name__ == "__main__":
    app = make_app()
    app.listen(8888)
    tornado.ioloop.IOLoop.current().start()